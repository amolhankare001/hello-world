<?php

namespace App\Http\Controllers;

use App\Http\Requests\Assessment\SubmitTestAttemptRequest;
use App\Models\Question;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use App\Services\AssessmentAttemptService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentAssessmentController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $student = $user->student;
        $enrollment = $this->currentEnrollment($student);
        $schoolClassId = $enrollment->division->school_class_id;

        $tests = Test::query()
            ->where('status', 'published')
            ->where(fn ($query) => $query
                ->whereNull('school_id')
                ->orWhere('school_id', $student->school_id))
            ->where(fn ($query) => $query
                ->whereNull('academic_year_id')
                ->orWhere('academic_year_id', $enrollment->academic_year_id))
            ->where(fn ($query) => $query
                ->whereNull('school_class_id')
                ->orWhere('school_class_id', $schoolClassId))
            ->where(fn ($query) => $query
                ->whereNull('available_from')
                ->orWhere('available_from', '<=', now()))
            ->where(fn ($query) => $query
                ->whereNull('available_until')
                ->orWhere('available_until', '>=', now()))
            ->whereHas('questions', fn ($query) => $query->where('is_active', true))
            ->with(['subject:id,name,name_marathi'])
            ->withCount([
                'questions' => fn ($query) => $query->where('is_active', true),
                'attempts as student_attempts_count' => fn ($query) => $query
                    ->where('student_id', $student->id),
                'attempts as in_progress_attempts_count' => fn ($query) => $query
                    ->where('student_id', $student->id)
                    ->where('status', 'in_progress'),
            ])
            ->orderByRaw("CASE type WHEN 'pre_test' THEN 1 WHEN 'reassessment' THEN 2 ELSE 3 END")
            ->orderBy('title_marathi')
            ->get()
            ->filter(fn (Test $test): bool => $test->questions_count >= $test->question_count)
            ->values();
        $recentAttempts = $student->testAttempts()
            ->where('status', 'completed')
            ->with('test:id,title,title_marathi,type')
            ->latest('submitted_at')
            ->limit(10)
            ->get();

        return view('assessments.index', compact('tests', 'recentAttempts'));
    }

    public function start(Request $request, Test $test): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $student = $user->student;
        $enrollment = $this->currentEnrollment($student);
        $this->assertAvailable($test, $student, $enrollment);

        $attempt = DB::transaction(function () use ($student, $enrollment, $test): TestAttempt {
            $lockedStudent = Student::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();
            $existingAttempt = $lockedStudent->testAttempts()
                ->whereBelongsTo($test)
                ->whereBelongsTo($enrollment->academicYear)
                ->where('status', 'in_progress')
                ->latest('started_at')
                ->first();

            if ($existingAttempt !== null) {
                return $existingAttempt;
            }

            $attemptNumber = $lockedStudent->testAttempts()
                ->whereBelongsTo($test)
                ->max('attempt_number') + 1;
            abort_if($attemptNumber > $test->max_attempts, 422, 'Maximum attempts have been used.');
            $questionQuery = $test->questions()
                ->where('is_active', true);

            if ($test->shuffle_questions) {
                $questionQuery->inRandomOrder();
            } else {
                $questionQuery->orderByPivot('sort_order');
            }

            $questionIds = $questionQuery
                ->limit($test->question_count)
                ->pluck('questions.id')
                ->all();
            abort_if($questionIds === [], 422, 'This assessment has no active questions.');

            return $lockedStudent->testAttempts()->create([
                'attempt_key' => (string) Str::uuid(),
                'test_id' => $test->id,
                'academic_year_id' => $enrollment->academic_year_id,
                'attempt_number' => $attemptNumber,
                'status' => 'in_progress',
                'started_at' => now(),
                'expires_at' => $test->duration_minutes !== null
                    ? now()->addMinutes($test->duration_minutes)
                    : null,
                'diagnosis' => ['question_ids' => $questionIds],
            ]);
        });

        return redirect()->route('assessments.attempts.show', $attempt);
    }

    public function show(
        TestAttempt $studentTestAttempt,
        AssessmentAttemptService $attemptService,
    ): View|RedirectResponse {
        if ($studentTestAttempt->status === 'completed') {
            return redirect()->route('assessments.attempts.result', $studentTestAttempt);
        }

        if ($studentTestAttempt->expires_at?->isPast()) {
            $attemptService->complete($studentTestAttempt, []);

            return redirect()->route('assessments.attempts.result', $studentTestAttempt);
        }

        $studentTestAttempt->load('test.subject');

        return view('assessments.show', [
            'attempt' => $studentTestAttempt,
            'questions' => $this->attemptQuestions($studentTestAttempt),
        ]);
    }

    public function submit(
        SubmitTestAttemptRequest $request,
        TestAttempt $studentTestAttempt,
        AssessmentAttemptService $attemptService,
    ): RedirectResponse {
        $attemptService->complete($studentTestAttempt, $request->validated('answers'));

        return redirect()->route('assessments.attempts.result', $studentTestAttempt);
    }

    public function result(TestAttempt $studentTestAttempt): View|RedirectResponse
    {
        if ($studentTestAttempt->status !== 'completed') {
            return redirect()->route('assessments.attempts.show', $studentTestAttempt);
        }

        $studentTestAttempt->load([
            'test.subject:id,name,name_marathi',
            'answers.question.options',
        ]);

        return view('assessments.result', [
            'attempt' => $studentTestAttempt,
            'questions' => $studentTestAttempt->answers->pluck('question')->keyBy('id'),
            'isMentorView' => false,
        ]);
    }

    private function assertAvailable(Test $test, Student $student, StudentEnrollment $enrollment): void
    {
        $schoolClassId = $enrollment->division->school_class_id;

        abort_unless(
            $test->status === 'published'
                && ($test->school_id === null || $test->school_id === $student->school_id)
                && ($test->academic_year_id === null || $test->academic_year_id === $enrollment->academic_year_id)
                && ($test->school_class_id === null || $test->school_class_id === $schoolClassId)
                && ($test->available_from === null || $test->available_from->isPast())
                && ($test->available_until === null || $test->available_until->isFuture())
                && $test->questions()->where('is_active', true)->count() >= $test->question_count,
            404,
        );
    }

    private function currentEnrollment(Student $student): StudentEnrollment
    {
        return $student->enrollments()
            ->where('status', 'active')
            ->with(['academicYear', 'division.schoolClass'])
            ->latest('enrolled_on')
            ->firstOrFail();
    }

    /**
     * @return Collection<int, Question>
     */
    private function attemptQuestions(TestAttempt $attempt): Collection
    {
        $questionIds = data_get($attempt->diagnosis, 'question_ids', []);
        $questionsById = Question::query()
            ->whereIn('id', $questionIds)
            ->with(['options' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->get()
            ->keyBy('id');

        return new Collection(
            collect($questionIds)
                ->map(fn (int $questionId): ?Question => $questionsById->get($questionId))
                ->filter()
                ->values()
                ->all(),
        );
    }
}
