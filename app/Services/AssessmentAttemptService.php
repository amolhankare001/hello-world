<?php

namespace App\Services;

use App\Models\Question;
use App\Models\StudentSkillEvent;
use App\Models\StudentSkillProgress;
use App\Models\TestAnswer;
use App\Models\TestAttempt;
use App\Models\TestQuestion;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AssessmentAttemptService
{
    public function __construct(private PracticeAnswerEvaluator $answerEvaluator) {}

    /**
     * @param  array<int|string, mixed>  $submittedAnswers
     */
    public function complete(TestAttempt $attempt, array $submittedAnswers): TestAttempt
    {
        return DB::transaction(function () use ($attempt, $submittedAnswers): TestAttempt {
            $lockedAttempt = TestAttempt::query()
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAttempt->status === 'completed') {
                return $lockedAttempt;
            }

            $lockedAttempt->load('test');
            $questionIds = data_get($lockedAttempt->diagnosis, 'question_ids', []);
            $questionsById = Question::query()
                ->whereIn('id', $questionIds)
                ->with(['options', 'skill:id,name,name_marathi', 'errorType:id,name,name_marathi'])
                ->get()
                ->keyBy('id');
            $questions = collect($questionIds)
                ->map(fn (int $questionId): ?Question => $questionsById->get($questionId))
                ->filter()
                ->values();

            if ($questions->isEmpty()) {
                throw new RuntimeException('The assessment attempt does not contain questions.');
            }

            $submittedAt = now();
            $durationSeconds = max(1, (int) $lockedAttempt->started_at->diffInSeconds($submittedAt));
            $durationPerQuestion = max(1, (int) floor($durationSeconds / $questions->count()));
            $acceptedAnswers = $lockedAttempt->expires_at?->isPast() ? [] : $submittedAnswers;
            $marksByQuestion = TestQuestion::query()
                ->where('test_id', $lockedAttempt->test_id)
                ->whereIn('question_id', $questionIds)
                ->get()
                ->keyBy('question_id');
            $responses = [];
            $rawScore = 0.0;
            $rawMaxScore = 0.0;
            $correctCount = 0;

            foreach ($questions as $question) {
                $submittedAnswer = $acceptedAnswers[$question->id] ?? null;

                if (! is_string($submittedAnswer) && ! is_array($submittedAnswer)) {
                    $submittedAnswer = null;
                }

                $evaluation = $this->answerEvaluator->evaluate($question, $submittedAnswer);
                $marks = (float) ($marksByQuestion->get($question->id)?->marks ?? $question->marks);
                $questionScore = $evaluation['is_correct'] ? $marks : 0.0;
                $correctCount += $evaluation['is_correct'] ? 1 : 0;
                $rawScore += $questionScore;
                $rawMaxScore += $marks;
                $response = [
                    'question_id' => $question->id,
                    'skill_id' => $question->skill_id,
                    'skill_name' => $question->skill->name,
                    'skill_name_marathi' => $question->skill->name_marathi,
                    'answer' => $evaluation['answer'],
                    'correct_answer' => $evaluation['correct_answer'],
                    'is_correct' => $evaluation['is_correct'],
                    'score' => $questionScore,
                    'max_score' => $marks,
                    'difficulty' => $question->difficulty,
                    'error_type_id' => $evaluation['is_correct'] ? null : $question->error_type_id,
                    'error_type' => $evaluation['is_correct'] ? null : $question->errorType?->name,
                    'error_type_marathi' => $evaluation['is_correct'] ? null : $question->errorType?->name_marathi,
                ];
                $answer = $this->storeAnswer($lockedAttempt, $question, $response, $durationPerQuestion, $submittedAt);
                $this->recordSkillEvent($lockedAttempt, $answer, $response, $durationPerQuestion, $submittedAt);
                $responses[] = $response;
            }

            $accuracy = round(($correctCount / $questions->count()) * 100, 2);
            $percentage = $rawMaxScore > 0 ? round(($rawScore / $rawMaxScore) * 100, 2) : 0;
            $comparison = $this->comparison($lockedAttempt, $percentage, $accuracy);
            $skillDiagnosis = $this->skillDiagnosis($responses, $lockedAttempt, $comparison);

            $lockedAttempt->update([
                'status' => 'completed',
                'submitted_at' => $submittedAt,
                'score' => round($rawScore, 2),
                'max_score' => round($rawMaxScore, 2),
                'percentage' => $percentage,
                'accuracy' => $accuracy,
                'duration_seconds' => $durationSeconds,
                'diagnosis' => [
                    'question_ids' => $questionIds,
                    'correct_count' => $correctCount,
                    'incorrect_count' => $questions->count() - $correctCount,
                    'skills' => $skillDiagnosis,
                    'comparison' => $comparison,
                ],
            ]);

            foreach ($skillDiagnosis as $diagnosis) {
                $this->updateSkillProgress($lockedAttempt, $diagnosis);
            }

            return $lockedAttempt->fresh(['test.subject', 'answers.question.options']);
        });
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function storeAnswer(
        TestAttempt $attempt,
        Question $question,
        array $response,
        int $durationSeconds,
        DateTimeInterface $answeredAt,
    ): TestAnswer {
        $selectedOptionId = null;

        if (
            in_array($question->type, ['mcq', 'image_selection', 'audio_selection'], true)
            && is_array($response['answer'])
            && count($response['answer']) === 1
        ) {
            $selectedOptionId = (int) reset($response['answer']);
        }

        return TestAnswer::query()->create([
            'test_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'question_option_id' => $selectedOptionId,
            'error_type_id' => $response['error_type_id'],
            'answer' => ['value' => $response['answer'], 'correct_answer' => $response['correct_answer']],
            'is_correct' => $response['is_correct'],
            'score' => $response['score'],
            'duration_seconds' => $durationSeconds,
            'answered_at' => $answeredAt,
        ]);
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function recordSkillEvent(
        TestAttempt $attempt,
        TestAnswer $answer,
        array $response,
        int $durationSeconds,
        DateTimeInterface $occurredAt,
    ): void {
        StudentSkillEvent::query()->create([
            'event_key' => (string) Str::uuid(),
            'student_id' => $attempt->student_id,
            'skill_id' => $response['skill_id'],
            'academic_year_id' => $attempt->academic_year_id,
            'activity_id' => null,
            'error_type_id' => $response['error_type_id'],
            'activity_type' => 'assessment',
            'source_type' => 'test_answer',
            'source_id' => $answer->id,
            'score' => $response['score'],
            'max_score' => $response['max_score'],
            'accuracy' => $response['is_correct'] ? 100 : 0,
            'duration_seconds' => $durationSeconds,
            'difficulty' => $response['difficulty'],
            'is_correct' => $response['is_correct'],
            'xp_awarded' => 0,
            'metadata' => [
                'attempt_key' => $attempt->attempt_key,
                'test_id' => $attempt->test_id,
                'test_type' => $attempt->test->type,
                'question_id' => $response['question_id'],
                'answer' => $response['answer'],
                'correct_answer' => $response['correct_answer'],
            ],
            'occurred_at' => $occurredAt,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $responses
     * @param  array<string, float|null>  $comparison
     * @return list<array<string, mixed>>
     */
    private function skillDiagnosis(array $responses, TestAttempt $attempt, array $comparison): array
    {
        $skillIds = collect($responses)->pluck('skill_id')->unique();
        $progressBySkill = StudentSkillProgress::query()
            ->where('student_id', $attempt->student_id)
            ->where('academic_year_id', $attempt->academic_year_id)
            ->whereIn('skill_id', $skillIds)
            ->get()
            ->keyBy('skill_id');

        return collect($responses)
            ->groupBy('skill_id')
            ->map(function (Collection $skillResponses) use ($attempt, $comparison, $progressBySkill): array {
                $correctCount = $skillResponses->where('is_correct', true)->count();
                $questionCount = $skillResponses->count();
                $score = round((float) $skillResponses->sum('score'), 2);
                $maxScore = round((float) $skillResponses->sum('max_score'), 2);
                $accuracy = round(($correctCount / $questionCount) * 100, 2);
                $firstResponse = $skillResponses->first();
                $progress = $progressBySkill->get($firstResponse['skill_id']);
                $preTestScore = $attempt->test->type === 'post_test'
                    ? (float) ($progress?->pre_test_score ?? 0)
                    : null;

                return [
                    'skill_id' => $firstResponse['skill_id'],
                    'skill_name' => $firstResponse['skill_name'],
                    'skill_name_marathi' => $firstResponse['skill_name_marathi'],
                    'question_count' => $questionCount,
                    'correct_count' => $correctCount,
                    'incorrect_count' => $questionCount - $correctCount,
                    'score' => $score,
                    'max_score' => $maxScore,
                    'accuracy' => $accuracy,
                    'classification' => $accuracy >= 80 ? 'strength' : ($accuracy < 60 ? 'needs_support' : 'developing'),
                    'errors' => $skillResponses
                        ->whereNotNull('error_type_id')
                        ->groupBy('error_type_id')
                        ->map(fn (Collection $errors): array => [
                            'error_type_id' => $errors->first()['error_type_id'],
                            'name' => $errors->first()['error_type'],
                            'name_marathi' => $errors->first()['error_type_marathi'],
                            'count' => $errors->count(),
                        ])
                        ->values()
                        ->all(),
                    'pre_test_score' => $preTestScore,
                    'post_test_score' => $attempt->test->type === 'post_test' ? $accuracy : null,
                    'percentage_point_improvement' => $preTestScore !== null ? round($accuracy - $preTestScore, 2) : null,
                    'relative_improvement_percent' => $preTestScore !== null && $preTestScore > 0
                        ? round((($accuracy - $preTestScore) / $preTestScore) * 100, 2)
                        : null,
                    'overall_comparison_available' => $comparison['pre_test_accuracy'] !== null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, float|null>
     */
    private function comparison(TestAttempt $attempt, float $percentage, float $accuracy): array
    {
        if ($attempt->test->type !== 'post_test') {
            return [
                'pre_test_percentage' => null,
                'post_test_percentage' => null,
                'pre_test_accuracy' => null,
                'post_test_accuracy' => null,
                'percentage_point_improvement' => null,
                'relative_improvement_percent' => null,
            ];
        }

        $preTestAttempt = TestAttempt::query()
            ->where('student_id', $attempt->student_id)
            ->where('academic_year_id', $attempt->academic_year_id)
            ->where('status', 'completed')
            ->whereHas('test', fn ($query) => $query
                ->where('type', 'pre_test')
                ->where('subject_id', $attempt->test->subject_id))
            ->latest('submitted_at')
            ->first(['percentage', 'accuracy']);
        $preTestPercentage = $preTestAttempt !== null
            ? (float) ($preTestAttempt->percentage ?? $preTestAttempt->accuracy)
            : null;
        $preTestAccuracy = $preTestAttempt !== null ? (float) $preTestAttempt->accuracy : null;

        return [
            'pre_test_percentage' => $preTestPercentage,
            'post_test_percentage' => $percentage,
            'pre_test_accuracy' => $preTestAccuracy,
            'post_test_accuracy' => $accuracy,
            'percentage_point_improvement' => $preTestPercentage !== null
                ? round($percentage - $preTestPercentage, 2)
                : null,
            'relative_improvement_percent' => $preTestPercentage !== null && $preTestPercentage > 0
                ? round((($percentage - $preTestPercentage) / $preTestPercentage) * 100, 2)
                : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $diagnosis
     */
    private function updateSkillProgress(TestAttempt $attempt, array $diagnosis): void
    {
        $progress = StudentSkillProgress::query()->firstOrCreate([
            'student_id' => $attempt->student_id,
            'skill_id' => $diagnosis['skill_id'],
            'academic_year_id' => $attempt->academic_year_id,
        ]);
        $progress = StudentSkillProgress::query()->whereKey($progress->id)->lockForUpdate()->firstOrFail();
        $totalAttempts = $progress->total_attempts + $diagnosis['question_count'];
        $totalCorrect = $progress->correct_attempts + $diagnosis['correct_count'];
        $cumulativeAccuracy = round(($totalCorrect / $totalAttempts) * 100, 2);
        $attributes = [
            'total_attempts' => $totalAttempts,
            'correct_attempts' => $totalCorrect,
            'accuracy' => $cumulativeAccuracy,
            'mastery_score' => $cumulativeAccuracy,
            'best_score' => max((float) $progress->best_score, (float) $diagnosis['accuracy']),
            'last_activity_at' => $attempt->submitted_at,
            'status' => $diagnosis['accuracy'] >= 80 ? 'mastered' : 'in_progress',
        ];

        if ($attempt->test->type === 'pre_test') {
            $attributes['pre_test_score'] = $diagnosis['accuracy'];
        } elseif ($attempt->test->type === 'post_test') {
            $attributes['post_test_score'] = $diagnosis['accuracy'];
            $attributes['improvement'] = $diagnosis['percentage_point_improvement'];
        }

        $progress->update($attributes);
    }
}
