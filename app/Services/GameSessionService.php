<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Game;
use App\Models\GameQuestion;
use App\Models\GameResult;
use App\Models\GameSession;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentSkillEvent;
use App\Models\StudentSkillProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GameSessionService
{
    public function __construct(
        private GameQuestionProvider $questionProvider,
        private GameScoreManager $scoreManager,
        private GameFeedbackManager $feedbackManager,
        private GameDifficultyManager $difficultyManager,
        private GameTimer $timer,
        private GameLives $lives,
        private GamificationService $gamificationService,
    ) {}

    public function start(Student $student, AcademicYear $academicYear, Game $game): GameSession
    {
        abort_unless($game->status === 'published', 404);
        $game->loadMissing(['levels', 'skills.subject']);

        return DB::transaction(function () use ($student, $academicYear, $game): GameSession {
            Student::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();
            $existingSession = GameSession::query()
                ->whereBelongsTo($student)
                ->whereBelongsTo($academicYear)
                ->whereBelongsTo($game)
                ->where('status', 'in_progress')
                ->latest('started_at')
                ->first();

            if (
                $existingSession !== null
                && ($existingSession->expires_at === null || ! $this->timer->isExpired($existingSession))
            ) {
                return $existingSession;
            }

            if ($existingSession !== null) {
                $existingSession = GameSession::query()
                    ->whereKey($existingSession->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $this->finalize($existingSession);
            }

            $skill = $this->primarySkill($game);
            $currentLevel = (int) StudentSkillProgress::query()
                ->where('student_id', $student->id)
                ->where('skill_id', $skill->id)
                ->where('academic_year_id', $academicYear->id)
                ->value('current_level') ?: 1;
            $level = $this->difficultyManager->selectLevel($game, $currentLevel);
            $configuration = $level->configuration ?? [];
            $lives = $this->lives->initial($configuration);
            $startedAt = now();
            $session = GameSession::query()->create([
                'session_key' => (string) Str::uuid(),
                'game_id' => $game->id,
                'game_level_id' => $level->id,
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'status' => 'in_progress',
                'difficulty' => $level->difficulty,
                'started_at' => $startedAt,
                'expires_at' => $this->timer->expiresAt($level, $startedAt),
                'server_state' => [
                    'lives_total' => $lives,
                    'lives_remaining' => $lives,
                    'score' => 0,
                    'answered_count' => 0,
                    'correct_count' => 0,
                    'incorrect_count' => 0,
                    'question_started_at' => $startedAt->toIso8601String(),
                ],
            ]);
            $session->questions()->createMany(
                $this->questionProvider->generate($game, $level, $game->skills),
            );

            return $session;
        });
    }

    /**
     * @param  array{value: mixed}  $answer
     * @return array<string, mixed>
     */
    public function answer(GameSession $gameSession, GameQuestion $gameQuestion, array $answer): array
    {
        return DB::transaction(function () use ($gameSession, $gameQuestion, $answer): array {
            Student::query()->whereKey($gameSession->student_id)->lockForUpdate()->firstOrFail();
            $session = GameSession::query()
                ->whereKey($gameSession->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->status === 'completed') {
                return [...$this->state($session), 'is_replay' => true];
            }

            if ($this->timer->isExpired($session)) {
                $this->finalize($session);

                return [...$this->state($session), 'is_replay' => false];
            }

            $question = GameQuestion::query()
                ->whereBelongsTo($session, 'session')
                ->whereKey($gameQuestion->id)
                ->lockForUpdate()
                ->firstOrFail();
            $existingAnswer = $question->answer()->first();

            if ($existingAnswer !== null) {
                return [
                    ...$this->state($session),
                    'feedback' => $this->feedbackManager->forAnswer($existingAnswer->is_correct),
                    'is_replay' => true,
                ];
            }

            $nextQuestionId = $session->questions()
                ->whereDoesntHave('answer')
                ->orderBy('sequence')
                ->value('id');
            abort_unless($nextQuestionId === $question->id, 409, 'Answer the current game question first.');

            $responseMs = $this->timer->responseMilliseconds($session);
            $submittedValue = (string) ($answer['value'] ?? '');
            $expectedValue = (string) data_get($question->expected_answer, 'value', '');
            $withinQuestionTime = $question->response_time_limit_ms === null
                || $responseMs <= $question->response_time_limit_ms;
            $isCorrect = $withinQuestionTime && hash_equals($expectedValue, $submittedValue);
            $score = $this->scoreManager->score($question, $isCorrect, $responseMs);
            $question->answer()->create([
                'answer' => ['value' => $submittedValue],
                'is_correct' => $isCorrect,
                'response_ms' => $responseMs,
                'score' => $score,
                'answered_at' => now(),
            ]);

            $state = $session->server_state ?? [];
            $state['answered_count'] = (int) data_get($state, 'answered_count', 0) + 1;
            $state['correct_count'] = (int) data_get($state, 'correct_count', 0) + ($isCorrect ? 1 : 0);
            $state['incorrect_count'] = (int) data_get($state, 'incorrect_count', 0) + ($isCorrect ? 0 : 1);
            $state['score'] = (int) data_get($state, 'score', 0) + $score;
            $state['lives_remaining'] = $this->lives->afterAnswer(
                (int) data_get($state, 'lives_remaining', 0),
                $isCorrect,
            );
            $state['question_started_at'] = now()->toIso8601String();
            $session->update(['server_state' => $state]);

            if (
                $state['answered_count'] >= $session->questions()->count()
                || $this->lives->isDepleted($state['lives_remaining'])
            ) {
                $this->finalize($session);
            }

            return [
                ...$this->state($session),
                'feedback' => $this->feedbackManager->forAnswer($isCorrect),
                'is_replay' => false,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function finishExpired(GameSession $gameSession): array
    {
        return DB::transaction(function () use ($gameSession): array {
            Student::query()->whereKey($gameSession->student_id)->lockForUpdate()->firstOrFail();
            $session = GameSession::query()
                ->whereKey($gameSession->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->status === 'completed') {
                return [...$this->state($session), 'is_replay' => true];
            }

            $state = $session->server_state ?? [];
            $hasNoLives = $this->lives->isDepleted((int) data_get($state, 'lives_remaining', 0));
            $hasNoQuestions = ! $session->questions()->whereDoesntHave('answer')->exists();
            $hasExpired = $this->timer->isExpired($session);
            abort_unless($hasNoLives || $hasNoQuestions || $hasExpired, 409, 'This game is still in progress.');
            $this->finalize($session);

            return [...$this->state($session), 'is_replay' => false];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function state(GameSession $gameSession): array
    {
        $gameSession->refresh()->loadMissing(['game', 'level', 'result']);
        $state = $gameSession->server_state ?? [];
        $nextQuestion = $gameSession->questions()
            ->whereDoesntHave('answer')
            ->orderBy('sequence')
            ->first();
        $questionCount = $gameSession->questions()->count();

        return [
            'session' => [
                'status' => $gameSession->status,
                'difficulty' => $gameSession->difficulty,
                'level' => $gameSession->level?->level,
                'level_name' => $gameSession->level?->name_marathi ?: $gameSession->level?->name,
                'expires_at' => $gameSession->expires_at?->toIso8601String(),
                'remaining_seconds' => $this->timer->remainingSeconds($gameSession),
            ],
            'game' => [
                'code' => $gameSession->game->code,
                'title' => $gameSession->game->title,
                'title_marathi' => $gameSession->game->title_marathi,
                'engine_key' => $gameSession->game->engine_key,
            ],
            'stats' => [
                'score' => (int) data_get($state, 'score', 0),
                'lives_total' => (int) data_get($state, 'lives_total', 0),
                'lives_remaining' => (int) data_get($state, 'lives_remaining', 0),
                'answered_count' => (int) data_get($state, 'answered_count', 0),
                'question_count' => $questionCount,
                'correct_count' => (int) data_get($state, 'correct_count', 0),
                'incorrect_count' => (int) data_get($state, 'incorrect_count', 0),
            ],
            'question' => $nextQuestion === null ? null : [
                'id' => $nextQuestion->id,
                'sequence' => $nextQuestion->sequence,
                'prompt' => $nextQuestion->prompt,
                'prompt_marathi' => $nextQuestion->prompt_marathi,
                'choices' => $nextQuestion->choices,
                'response_time_limit_ms' => $nextQuestion->response_time_limit_ms,
            ],
            'completed' => $gameSession->status === 'completed',
            'result' => $gameSession->result === null ? null : [
                'score' => $gameSession->result->score,
                'max_score' => $gameSession->result->max_score,
                'accuracy' => (float) $gameSession->result->accuracy,
                'xp_awarded' => $gameSession->result->xp_awarded,
            ],
        ];
    }

    private function primarySkill(Game $game): Skill
    {
        $skill = $game->skills
            ->sortByDesc(fn (Skill $candidate): float => (float) $candidate->pivot->weight)
            ->first();
        abort_if($skill === null, 422, 'This game is not connected to a skill.');

        return $skill;
    }

    private function finalize(GameSession $gameSession): GameResult
    {
        $gameSession->loadMissing([
            'academicYear',
            'game',
            'level',
            'student.school',
            'questions.answer',
        ]);
        $existingResult = $gameSession->result()->first();

        if ($existingResult !== null) {
            return $existingResult;
        }

        $questionCount = $gameSession->questions->count();
        $answeredCount = $gameSession->questions->whereNotNull('answer')->count();
        $correctCount = $gameSession->questions
            ->filter(fn (GameQuestion $question): bool => $question->answer?->is_correct === true)
            ->count();
        $score = (int) $gameSession->questions->sum(
            fn (GameQuestion $question): int => $question->answer?->score ?? 0,
        );
        $maxScore = (int) $gameSession->questions->sum('max_score');
        $accuracy = $questionCount === 0 ? 0 : round(($correctCount / $questionCount) * 100, 2);
        $durationSeconds = max(1, (int) $gameSession->started_at->diffInSeconds(now()));
        $previousBest = GameResult::query()
            ->whereHas('session', fn ($query) => $query
                ->where('student_id', $gameSession->student_id)
                ->where('game_id', $gameSession->game_id))
            ->max('accuracy');
        $xpAwarded = 0;

        if ($answeredCount > 0) {
            $gamification = $this->gamificationService->recordCompletion(
                $gameSession->student,
                $gameSession->academicYear,
                'game_session',
                $gameSession->id,
                $gameSession->questions->first()?->skill_id,
                $accuracy,
                $durationSeconds,
                $previousBest !== null && $accuracy > (float) $previousBest,
            );
            $xpAwarded = $gamification['xp_awarded'];
            $this->recordSkillEvidence($gameSession, $xpAwarded);
            $this->updateSkillProgress($gameSession);
        }

        $result = $gameSession->result()->create([
            'score' => $score,
            'max_score' => $maxScore,
            'correct_count' => $correctCount,
            'incorrect_count' => max(0, $questionCount - $correctCount),
            'accuracy' => $accuracy,
            'duration_seconds' => $durationSeconds,
            'xp_awarded' => $xpAwarded,
            'result_payload' => [
                'answered_count' => $answeredCount,
                'missed_count' => max(0, $questionCount - $answeredCount),
                'level' => $gameSession->level?->level,
                'difficulty' => $gameSession->difficulty,
                'lives_remaining' => (int) data_get($gameSession->server_state, 'lives_remaining', 0),
                'target_met' => $gameSession->level?->target_score === null
                    || $score >= $gameSession->level->target_score,
            ],
            'validated_at' => now(),
        ]);
        $gameSession->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return $result;
    }

    private function recordSkillEvidence(GameSession $gameSession, int $xpAwarded): void
    {
        foreach ($gameSession->questions as $index => $question) {
            StudentSkillEvent::query()->create([
                'event_key' => (string) Str::uuid(),
                'student_id' => $gameSession->student_id,
                'skill_id' => $question->skill_id,
                'academic_year_id' => $gameSession->academic_year_id,
                'activity_type' => 'game',
                'source_type' => 'game_session',
                'source_id' => $gameSession->id,
                'score' => $question->answer?->score ?? 0,
                'max_score' => $question->max_score,
                'accuracy' => $question->answer?->is_correct ? 100 : 0,
                'duration_seconds' => $question->answer === null
                    ? null
                    : max(1, (int) ceil($question->answer->response_ms / 1000)),
                'difficulty' => $question->difficulty,
                'is_correct' => $question->answer?->is_correct ?? false,
                'xp_awarded' => $index === 0 ? $xpAwarded : 0,
                'metadata' => [
                    'game_id' => $gameSession->game_id,
                    'game_level_id' => $gameSession->game_level_id,
                    'game_question_id' => $question->id,
                    'game_answer_id' => $question->answer?->id,
                    'was_answered' => $question->answer !== null,
                ],
                'occurred_at' => $question->answer?->answered_at ?? $gameSession->completed_at ?? now(),
            ]);
        }
    }

    private function updateSkillProgress(GameSession $gameSession): void
    {
        foreach ($gameSession->questions->groupBy('skill_id') as $skillId => $questions) {
            $questionCount = $questions->count();
            $correctCount = $questions->filter(
                fn (GameQuestion $question): bool => $question->answer?->is_correct === true,
            )->count();
            $accuracy = round(($correctCount / $questionCount) * 100, 2);
            $progress = StudentSkillProgress::query()->firstOrCreate([
                'student_id' => $gameSession->student_id,
                'skill_id' => $skillId,
                'academic_year_id' => $gameSession->academic_year_id,
            ]);
            $progress = StudentSkillProgress::query()
                ->whereKey($progress->id)
                ->lockForUpdate()
                ->firstOrFail();
            $totalAttempts = $progress->total_attempts + $questionCount;
            $totalCorrect = $progress->correct_attempts + $correctCount;
            $gameCount = $progress->game_count + 1;
            $cumulativeAccuracy = round(($totalCorrect / $totalAttempts) * 100, 2);
            $averageScore = round(
                (((float) $progress->average_score * $progress->game_count) + $accuracy) / $gameCount,
                2,
            );
            $currentLevel = $this->difficultyManager->nextLevel(
                $progress->current_level,
                $accuracy,
                $gameSession->level?->configuration ?? [],
            );

            $progress->update([
                'current_level' => $currentLevel,
                'mastery_score' => $cumulativeAccuracy,
                'total_attempts' => $totalAttempts,
                'correct_attempts' => $totalCorrect,
                'accuracy' => $cumulativeAccuracy,
                'best_score' => max((float) $progress->best_score, $accuracy),
                'average_score' => $averageScore,
                'game_count' => $gameCount,
                'last_activity_at' => now(),
                'status' => $cumulativeAccuracy >= 80 ? 'mastered' : 'in_progress',
            ]);
        }
    }
}
