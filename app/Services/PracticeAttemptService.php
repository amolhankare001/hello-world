<?php

namespace App\Services;

use App\Models\PracticeAttempt;
use App\Models\Question;
use App\Models\StudentSkillEvent;
use App\Models\StudentSkillProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PracticeAttemptService
{
    public function __construct(
        private PracticeAnswerEvaluator $answerEvaluator,
        private GamificationService $gamificationService,
    ) {}

    /**
     * @param  array<int|string, mixed>  $submittedAnswers
     */
    public function complete(PracticeAttempt $attempt, array $submittedAnswers): PracticeAttempt
    {
        return DB::transaction(function () use ($attempt, $submittedAnswers): PracticeAttempt {
            $lockedAttempt = PracticeAttempt::query()
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAttempt->status === 'completed') {
                return $lockedAttempt;
            }

            $lockedAttempt->load(['academicYear', 'student', 'practiceActivity.activity.skillLevel']);
            $questionIds = data_get($lockedAttempt->answers, 'question_ids', []);
            $questionsById = Question::query()
                ->whereIn('id', $questionIds)
                ->with('options')
                ->get()
                ->keyBy('id');
            $questions = collect($questionIds)
                ->map(fn (int $questionId): ?Question => $questionsById->get($questionId))
                ->filter();

            if ($questions->isEmpty()) {
                throw new RuntimeException('The practice attempt does not contain questions.');
            }

            $activity = $lockedAttempt->practiceActivity->activity;
            $completedAt = now();
            $durationSeconds = max(1, (int) $lockedAttempt->started_at->diffInSeconds($completedAt));
            $durationPerQuestion = max(1, (int) floor($durationSeconds / $questions->count()));
            $correctCount = 0;
            $rawScore = 0.0;
            $rawMaxScore = 0.0;
            $responses = [];

            foreach ($questions as $question) {
                $submittedAnswer = $submittedAnswers[$question->id] ?? null;

                if (! is_string($submittedAnswer) && ! is_array($submittedAnswer)) {
                    $submittedAnswer = null;
                }

                $evaluation = $this->answerEvaluator->evaluate($question, $submittedAnswer);
                $marks = (float) $question->marks;
                $questionScore = $evaluation['is_correct'] ? $marks : 0.0;
                $correctCount += $evaluation['is_correct'] ? 1 : 0;
                $rawScore += $questionScore;
                $rawMaxScore += $marks;
                $responses[] = [
                    'question_id' => $question->id,
                    'answer' => $evaluation['answer'],
                    'correct_answer' => $evaluation['correct_answer'],
                    'is_correct' => $evaluation['is_correct'],
                    'score' => $questionScore,
                    'max_score' => $marks,
                    'difficulty' => $question->difficulty,
                    'error_type_id' => $evaluation['is_correct'] ? null : $question->error_type_id,
                ];
            }

            $questionCount = $questions->count();
            $incorrectCount = $questionCount - $correctCount;
            $accuracy = round(($correctCount / $questionCount) * 100, 2);
            $score = $rawMaxScore > 0
                ? round(($rawScore / $rawMaxScore) * (float) $activity->max_score, 2)
                : 0;
            $progress = StudentSkillProgress::query()
                ->where('student_id', $lockedAttempt->student_id)
                ->where('skill_id', $activity->skill_id)
                ->where('academic_year_id', $lockedAttempt->academic_year_id)
                ->first();
            $isPersonalBest = $progress !== null
                && $progress->practice_count > 0
                && $score > (float) $progress->best_score;

            $lockedAttempt->update([
                'status' => 'completed',
                'completed_at' => $completedAt,
                'correct_count' => $correctCount,
                'incorrect_count' => $incorrectCount,
                'score' => $score,
                'accuracy' => $accuracy,
                'duration_seconds' => $durationSeconds,
                'answers' => [
                    'responses' => $responses,
                    'raw_score' => $rawScore,
                    'raw_max_score' => $rawMaxScore,
                ],
            ]);

            $gamification = $this->gamificationService->recordCompletion(
                $lockedAttempt->student,
                $lockedAttempt->academicYear,
                'practice_attempt',
                $lockedAttempt->id,
                $activity->skill_id,
                $accuracy,
                $durationSeconds,
                $isPersonalBest,
            );
            $this->recordSkillEvents(
                $lockedAttempt,
                $responses,
                $durationPerQuestion,
                $completedAt,
                $gamification['xp_awarded'],
            );
            $this->updateSkillProgress($lockedAttempt, $accuracy, $score, $questionCount, $correctCount);

            return $lockedAttempt->fresh();
        });
    }

    /**
     * @param  list<array<string, mixed>>  $responses
     */
    private function recordSkillEvents(
        PracticeAttempt $attempt,
        array $responses,
        int $durationPerQuestion,
        \DateTimeInterface $occurredAt,
        int $xpAwarded,
    ): void {
        $activity = $attempt->practiceActivity->activity;

        foreach ($responses as $index => $response) {
            StudentSkillEvent::query()->create([
                'event_key' => (string) Str::uuid(),
                'student_id' => $attempt->student_id,
                'skill_id' => $activity->skill_id,
                'academic_year_id' => $attempt->academic_year_id,
                'activity_id' => $activity->id,
                'error_type_id' => $response['error_type_id'],
                'activity_type' => 'practice',
                'source_type' => 'practice_attempt_question',
                'source_id' => $attempt->id,
                'score' => $response['score'],
                'max_score' => $response['max_score'],
                'accuracy' => $response['is_correct'] ? 100 : 0,
                'duration_seconds' => $durationPerQuestion,
                'difficulty' => $response['difficulty'],
                'is_correct' => $response['is_correct'],
                'xp_awarded' => $index === 0 ? $xpAwarded : 0,
                'metadata' => [
                    'attempt_key' => $attempt->attempt_key,
                    'question_id' => $response['question_id'],
                    'answer' => $response['answer'],
                    'correct_answer' => $response['correct_answer'],
                ],
                'occurred_at' => $occurredAt,
            ]);
        }
    }

    private function updateSkillProgress(
        PracticeAttempt $attempt,
        float $attemptAccuracy,
        float $score,
        int $questionCount,
        int $correctCount,
    ): void {
        $activity = $attempt->practiceActivity->activity;
        $progress = StudentSkillProgress::query()->firstOrCreate([
            'student_id' => $attempt->student_id,
            'skill_id' => $activity->skill_id,
            'academic_year_id' => $attempt->academic_year_id,
        ]);
        $progress = StudentSkillProgress::query()->whereKey($progress->id)->lockForUpdate()->firstOrFail();
        $totalAttempts = $progress->total_attempts + $questionCount;
        $totalCorrect = $progress->correct_attempts + $correctCount;
        $practiceCount = $progress->practice_count + 1;
        $cumulativeAccuracy = round(($totalCorrect / $totalAttempts) * 100, 2);
        $averageScore = round(
            (((float) $progress->average_score * $progress->practice_count) + $score) / $practiceCount,
            2,
        );
        $configuration = $attempt->practiceActivity->configuration ?? [];
        $difficultyUpAccuracy = (float) data_get($configuration, 'difficulty_up_accuracy', 80);
        $remedialAccuracy = (float) data_get($configuration, 'remedial_accuracy', 60);
        $minimumDifficulty = (int) data_get($configuration, 'minimum_difficulty', 1);
        $maximumDifficulty = (int) data_get($configuration, 'maximum_difficulty', 5);
        $currentLevel = $progress->current_level;

        if ($attemptAccuracy >= $difficultyUpAccuracy) {
            $currentLevel = min($maximumDifficulty, $currentLevel + 1);
        } elseif ($attemptAccuracy < $remedialAccuracy) {
            $currentLevel = max($minimumDifficulty, $currentLevel - 1);
        }

        $masteryThreshold = (float) ($activity->skillLevel?->mastery_threshold ?? 80);

        $progress->update([
            'current_level' => $currentLevel,
            'mastery_score' => $cumulativeAccuracy,
            'total_attempts' => $totalAttempts,
            'correct_attempts' => $totalCorrect,
            'accuracy' => $cumulativeAccuracy,
            'best_score' => max((float) $progress->best_score, $score),
            'average_score' => $averageScore,
            'practice_count' => $practiceCount,
            'last_activity_at' => $attempt->completed_at,
            'status' => $cumulativeAccuracy >= $masteryThreshold ? 'mastered' : 'in_progress',
        ]);
    }
}
