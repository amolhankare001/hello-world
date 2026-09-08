<?php

namespace App\Http\Controllers;

use App\Http\Requests\Practice\SubmitPracticeAttemptRequest;
use App\Models\Activity;
use App\Models\PracticeAttempt;
use App\Models\Question;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentSkillProgress;
use App\Models\User;
use App\Services\PracticeAttemptService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentPracticeController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $student = $user->student;

        $activities = Activity::query()
            ->where('type', 'practice')
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where(fn ($query) => $query
                ->whereNull('school_id')
                ->orWhere('school_id', $student->school_id))
            ->whereHas('practiceActivity')
            ->whereHas('questions', fn ($query) => $query->where('is_active', true))
            ->with([
                'practiceActivity',
                'skill:id,subject_id,name,name_marathi',
                'skill.subject:id,name,name_marathi',
            ])
            ->withCount(['questions' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('difficulty')
            ->orderBy('title_marathi')
            ->get();

        $recentAttempts = $student->practiceAttempts()
            ->where('status', 'completed')
            ->with('practiceActivity.activity:id,title,title_marathi')
            ->latest('completed_at')
            ->limit(10)
            ->get();

        return view('practice.index', compact('activities', 'recentAttempts'));
    }

    public function start(Request $request, Activity $activity): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $student = $user->student;
        $this->assertAvailable($activity, $student);
        $activity->load('practiceActivity');
        $enrollment = $this->currentEnrollment($student);
        $practiceActivity = $activity->practiceActivity;

        $existingAttempt = $student->practiceAttempts()
            ->whereBelongsTo($practiceActivity)
            ->whereBelongsTo($enrollment->academicYear)
            ->where('status', 'in_progress')
            ->latest('started_at')
            ->first();

        if ($existingAttempt !== null) {
            return redirect()->route('practice.attempts.show', $existingAttempt);
        }

        $difficulty = (int) StudentSkillProgress::query()
            ->where('student_id', $student->id)
            ->where('skill_id', $activity->skill_id)
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->value('current_level') ?: $activity->difficulty;
        $questionQuery = $activity->questions()
            ->where('is_active', true)
            ->orderByRaw('ABS(difficulty - ?)', [$difficulty]);

        if ($practiceActivity->randomize_questions) {
            $questionQuery->inRandomOrder();
        } else {
            $questionQuery->orderBy('id');
        }

        $questionIds = $questionQuery
            ->limit($practiceActivity->question_count)
            ->pluck('id')
            ->all();
        abort_if($questionIds === [], 422, 'This practice activity has no active questions.');

        $attempt = $student->practiceAttempts()->create([
            'attempt_key' => (string) Str::uuid(),
            'practice_activity_id' => $practiceActivity->id,
            'academic_year_id' => $enrollment->academic_year_id,
            'status' => 'in_progress',
            'difficulty' => max(1, min(5, $difficulty)),
            'started_at' => now(),
            'answers' => ['question_ids' => $questionIds],
        ]);

        return redirect()->route('practice.attempts.show', $attempt);
    }

    public function show(PracticeAttempt $practiceAttempt): View|RedirectResponse
    {
        if ($practiceAttempt->status === 'completed') {
            return redirect()->route('practice.attempts.result', $practiceAttempt);
        }

        $practiceAttempt->load('practiceActivity.activity.skill');
        $questions = $this->attemptQuestions($practiceAttempt);

        return view('practice.show', [
            'attempt' => $practiceAttempt,
            'questions' => $questions,
        ]);
    }

    public function submit(
        SubmitPracticeAttemptRequest $request,
        PracticeAttempt $practiceAttempt,
        PracticeAttemptService $attemptService,
    ): RedirectResponse {
        $attemptService->complete($practiceAttempt, $request->validated('answers'));

        return redirect()->route('practice.attempts.result', $practiceAttempt);
    }

    public function result(PracticeAttempt $practiceAttempt): View|RedirectResponse
    {
        if ($practiceAttempt->status !== 'completed') {
            return redirect()->route('practice.attempts.show', $practiceAttempt);
        }

        $practiceAttempt->load('practiceActivity.activity.skill');
        $questionIds = collect(data_get($practiceAttempt->answers, 'responses', []))
            ->pluck('question_id')
            ->all();
        $questions = Question::query()
            ->whereIn('id', $questionIds)
            ->with('options')
            ->get()
            ->keyBy('id');

        return view('practice.result', [
            'attempt' => $practiceAttempt,
            'questions' => $questions,
            'responses' => data_get($practiceAttempt->answers, 'responses', []),
        ]);
    }

    private function assertAvailable(Activity $activity, Student $student): void
    {
        abort_unless(
            $activity->type === 'practice'
                && $activity->status === 'published'
                && $activity->published_at !== null
                && ($activity->school_id === null || $activity->school_id === $student->school_id)
                && $activity->practiceActivity()->exists()
                && $activity->questions()->where('is_active', true)->exists(),
            404,
        );
    }

    private function currentEnrollment(Student $student): StudentEnrollment
    {
        return $student->enrollments()
            ->where('status', 'active')
            ->with('academicYear')
            ->latest('enrolled_on')
            ->firstOrFail();
    }

    /**
     * @return Collection<int, Question>
     */
    private function attemptQuestions(PracticeAttempt $attempt): Collection
    {
        $questionIds = data_get($attempt->answers, 'question_ids', []);
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
