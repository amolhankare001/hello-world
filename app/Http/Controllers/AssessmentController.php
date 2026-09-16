<?php

namespace App\Http\Controllers;

use App\Enums\RoleCode;
use App\Http\Requests\Assessment\StoreAssessmentRequest;
use App\Http\Requests\Assessment\UpdateAssessmentRequest;
use App\Models\AcademicYear;
use App\Models\Question;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Test::class);

        /** @var User $user */
        $user = request()->user();
        $tests = Test::query()
            ->when(! $user->hasRole(RoleCode::SuperAdmin), fn ($query) => $query
                ->where(fn ($scope) => $scope
                    ->whereNull('school_id')
                    ->orWhere('school_id', $user->school_id)))
            ->with([
                'subject:id,name,name_marathi',
                'school:id,name,name_marathi',
                'schoolClass:id,name,name_marathi',
            ])
            ->withCount(['questions', 'attempts as completed_attempts_count' => fn ($query) => $query
                ->where('status', 'completed')
                ->when(! $user->hasRole(RoleCode::SuperAdmin), fn ($attemptQuery) => $attemptQuery
                    ->whereHas('student', fn ($studentQuery) => $studentQuery
                        ->where('school_id', $user->school_id)))])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(15);

        return view('assessment-management.index', compact('tests'));
    }

    public function create(): View
    {
        Gate::authorize('create', Test::class);

        return view('assessment-management.create', $this->formData(request()->user()));
    }

    public function store(StoreAssessmentRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validated();
        $test = DB::transaction(function () use ($request, $validated): Test {
            $test = Test::query()->create($this->attributes($request->user(), $validated));
            $this->syncQuestions($test, $validated['question_ids']);

            return $test;
        });
        $auditLogger->record($request->user(), 'assessment.created', $request, $test);

        return redirect()->route('tests.show', $test)->with('status', 'Assessment created successfully.');
    }

    public function show(Test $test): View
    {
        Gate::authorize('view', $test);
        /** @var User $user */
        $user = request()->user();
        $test->load([
            'subject:id,name,name_marathi',
            'school:id,name,name_marathi',
            'schoolClass:id,name,name_marathi',
            'academicYear:id,name',
            'questions' => fn ($query) => $query
                ->with(['skill:id,name,name_marathi', 'errorType:id,name,name_marathi'])
                ->orderByPivot('sort_order'),
            'attempts' => fn ($query) => $query
                ->where('status', 'completed')
                ->when(! $user->hasRole(RoleCode::SuperAdmin), fn ($attemptQuery) => $attemptQuery
                    ->whereHas('student', fn ($studentQuery) => $studentQuery
                        ->where('school_id', $user->school_id)))
                ->with([
                    'student:id,user_id,student_number',
                    'student.user:id,name',
                    'answers.errorType:id,name,name_marathi',
                ])
                ->latest('submitted_at')
                ->limit(50),
        ]);
        $commonErrors = $test->attempts
            ->flatMap->answers
            ->whereNotNull('error_type_id')
            ->groupBy('error_type_id')
            ->map(fn (Collection $answers): array => [
                'name' => $answers->first()->errorType?->name,
                'name_marathi' => $answers->first()->errorType?->name_marathi,
                'count' => $answers->count(),
            ])
            ->sortByDesc('count')
            ->values();

        return view('assessment-management.show', compact('test', 'commonErrors'));
    }

    public function edit(Test $test): View
    {
        Gate::authorize('update', $test);
        $test->load('questions:id');

        return view('assessment-management.edit', [
            'test' => $test,
            'selectedQuestionIds' => $test->questions->modelKeys(),
            ...$this->formData(request()->user()),
        ]);
    }

    public function update(
        UpdateAssessmentRequest $request,
        Test $test,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $validated = $request->validated();
        DB::transaction(function () use ($request, $test, $validated): void {
            $test->update($this->attributes($request->user(), $validated, $test));
            $this->syncQuestions($test, $validated['question_ids']);
        });
        $auditLogger->record($request->user(), 'assessment.updated', $request, $test);

        return redirect()->route('tests.show', $test)->with('status', 'Assessment updated successfully.');
    }

    public function destroy(Test $test): RedirectResponse
    {
        abort(405);
    }

    public function attempt(Test $test, TestAttempt $testAttempt): View
    {
        $testAttempt->load([
            'test.subject:id,name,name_marathi',
            'student:id,user_id,student_number',
            'student.user:id,name',
            'answers.question.options',
        ]);
        Gate::authorize('view', $testAttempt->test);
        abort_unless($testAttempt->status === 'completed', 404);

        return view('assessments.result', [
            'attempt' => $testAttempt,
            'questions' => $testAttempt->answers->pluck('question')->keyBy('id'),
            'isMentorView' => true,
        ]);
    }

    /**
     * @return array{
     *     schools: Collection<int, School>,
     *     academicYears: Collection<int, AcademicYear>,
     *     schoolClasses: Collection<int, SchoolClass>,
     *     subjects: Collection<int, Subject>,
     *     questions: Collection<int, Question>
     * }
     */
    private function formData(User $user): array
    {
        $schools = $user->hasRole(RoleCode::SuperAdmin)
            ? School::query()->orderBy('name')->orderBy('id')->get()
            : School::query()->whereKey($user->school_id)->get();
        $schoolIds = $schools->modelKeys();
        $academicYears = AcademicYear::query()
            ->whereIn('school_id', $schoolIds)
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->get();
        $schoolClasses = SchoolClass::query()
            ->whereIn('school_id', $schoolIds)
            ->where('is_active', true)
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();
        $subjects = Subject::query()
            ->when(! $user->hasRole(RoleCode::SuperAdmin), fn ($query) => $query
                ->where(fn ($scope) => $scope
                    ->whereNull('school_id')
                    ->orWhere('school_id', $user->school_id)))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $questions = Question::query()
            ->where('is_active', true)
            ->when(! $user->hasRole(RoleCode::SuperAdmin), fn ($query) => $query
                ->where(fn ($scope) => $scope
                    ->whereNull('activity_id')
                    ->orWhereHas('activity', fn ($activityQuery) => $activityQuery
                        ->whereNull('school_id')
                        ->orWhere('school_id', $user->school_id))))
            ->with([
                'skill:id,subject_id,name,name_marathi',
                'activity:id,school_id,title,title_marathi',
            ])
            ->orderBy('skill_id')
            ->orderBy('difficulty')
            ->orderBy('id')
            ->get();

        return compact('schools', 'academicYears', 'schoolClasses', 'subjects', 'questions');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributes(User $user, array $validated, ?Test $test = null): array
    {
        return [
            'school_id' => $user->hasRole(RoleCode::SuperAdmin)
                ? ($validated['school_id'] ?? null)
                : $user->school_id,
            'academic_year_id' => $validated['academic_year_id'] ?? null,
            'school_class_id' => $validated['school_class_id'] ?? null,
            'subject_id' => $validated['subject_id'],
            'created_by' => $test?->created_by ?? $user->id,
            'code' => $validated['code'],
            'type' => $validated['type'],
            'title' => $validated['title'],
            'title_marathi' => $validated['title_marathi'],
            'instructions' => $validated['instructions'] ?? null,
            'instructions_marathi' => $validated['instructions_marathi'] ?? null,
            'duration_minutes' => $validated['duration_minutes'],
            'difficulty' => $validated['difficulty'],
            'question_count' => $validated['question_count'],
            'max_attempts' => $validated['max_attempts'],
            'passing_score' => $validated['passing_score'] ?? null,
            'shuffle_questions' => $validated['shuffle_questions'],
            'status' => $validated['status'],
            'available_from' => $validated['available_from'] ?? null,
            'available_until' => $validated['available_until'] ?? null,
        ];
    }

    /**
     * @param  list<int|string>  $questionIds
     */
    private function syncQuestions(Test $test, array $questionIds): void
    {
        $questions = Question::query()
            ->whereIn('id', $questionIds)
            ->get(['id', 'marks'])
            ->keyBy('id');
        $pivot = collect($questionIds)->mapWithKeys(
            fn (int|string $questionId, int $index): array => [
                (int) $questionId => [
                    'sort_order' => $index + 1,
                    'marks' => $questions->get((int) $questionId)?->marks ?? 1,
                ],
            ],
        );

        $test->questions()->sync($pivot->all());
    }
}
