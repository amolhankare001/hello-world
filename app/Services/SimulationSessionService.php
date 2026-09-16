<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Simulation;
use App\Models\SimulationChallenge;
use App\Models\SimulationResult;
use App\Models\SimulationSession;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentSkillEvent;
use App\Models\StudentSkillProgress;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SimulationSessionService
{
    public function __construct(
        private SimulationChallengeProvider $challengeProvider,
        private GameFeedbackManager $feedbackManager,
        private GameDifficultyManager $difficultyManager,
        private GamificationService $gamificationService,
    ) {}

    public function start(Student $student, AcademicYear $academicYear, Simulation $simulation): SimulationSession
    {
        abort_unless($simulation->status === 'published', 404);
        $simulation->loadMissing('skills.subject');

        return DB::transaction(function () use ($student, $academicYear, $simulation): SimulationSession {
            Student::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();
            $existingSession = SimulationSession::query()
                ->whereBelongsTo($student)
                ->whereBelongsTo($academicYear)
                ->whereBelongsTo($simulation)
                ->where('status', 'in_progress')
                ->latest('started_at')
                ->first();

            if ($existingSession !== null) {
                return $existingSession;
            }

            $skill = $this->primarySkill($simulation);
            $difficulty = (int) StudentSkillProgress::query()
                ->where('student_id', $student->id)
                ->where('skill_id', $skill->id)
                ->where('academic_year_id', $academicYear->id)
                ->value('current_level') ?: 1;
            $startedAt = now();
            $session = SimulationSession::query()->create([
                'session_key' => (string) Str::uuid(),
                'simulation_id' => $simulation->id,
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'status' => 'in_progress',
                'difficulty' => max(1, min(5, $difficulty)),
                'started_at' => $startedAt,
                'state' => [
                    'current_sequence' => 1,
                    'score' => 0,
                    'successful_count' => 0,
                    'unsuccessful_count' => 0,
                    'challenge_started_at' => $startedAt->toIso8601String(),
                ],
            ]);
            $session->challenges()->createMany(
                $this->challengeProvider->generate($simulation, $session->difficulty, $simulation->skills),
            );

            return $session;
        });
    }

    /**
     * @param  array<string, mixed>  $submittedState
     * @return array<string, mixed>
     */
    public function submit(
        SimulationSession $simulationSession,
        SimulationChallenge $simulationChallenge,
        array $submittedState,
    ): array {
        return DB::transaction(function () use (
            $simulationSession,
            $simulationChallenge,
            $submittedState,
        ): array {
            Student::query()->whereKey($simulationSession->student_id)->lockForUpdate()->firstOrFail();
            $session = SimulationSession::query()
                ->with('simulation')
                ->whereKey($simulationSession->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->status === 'completed') {
                return [...$this->state($session), 'is_replay' => true];
            }

            $challenge = SimulationChallenge::query()
                ->whereBelongsTo($session, 'session')
                ->whereKey($simulationChallenge->id)
                ->lockForUpdate()
                ->firstOrFail();
            $state = $session->state ?? [];
            abort_unless(
                $challenge->sequence === (int) data_get($state, 'current_sequence', 1),
                409,
                'Complete the current simulation challenge first.',
            );

            $attemptNumber = $challenge->events()->count() + 1;
            $maximumAttempts = max(1, min(
                5,
                (int) data_get($session->simulation->configuration, 'max_attempts', 3),
            ));
            abort_if($attemptNumber > $maximumAttempts, 409, 'This challenge is already complete.');

            $canonicalState = $this->canonicalState($challenge->expected_state, $submittedState);
            $expectedState = $this->canonicalState($challenge->expected_state, $challenge->expected_state);
            $isSuccess = hash_equals(
                json_encode($expectedState, JSON_THROW_ON_ERROR),
                json_encode($canonicalState, JSON_THROW_ON_ERROR),
            );
            $score = $isSuccess ? max(40, $challenge->max_score - (($attemptNumber - 1) * 20)) : 0;
            $challengeStartedAt = Carbon::parse(
                (string) data_get($state, 'challenge_started_at', $session->started_at->toIso8601String()),
            );
            $responseMs = max(1, (int) round($challengeStartedAt->diffInMilliseconds(now())));
            $event = $challenge->events()->create([
                'simulation_session_id' => $session->id,
                'attempt_number' => $attemptNumber,
                'event_type' => 'submission',
                'payload' => $canonicalState,
                'is_success' => $isSuccess,
                'score' => $score,
                'response_ms' => $responseMs,
                'occurred_at' => now(),
            ]);

            $shouldAdvance = $isSuccess || $attemptNumber >= $maximumAttempts;

            if ($shouldAdvance) {
                $state['score'] = (int) data_get($state, 'score', 0) + $score;
                $state['successful_count'] = (int) data_get($state, 'successful_count', 0) + ($isSuccess ? 1 : 0);
                $state['unsuccessful_count'] = (int) data_get($state, 'unsuccessful_count', 0) + ($isSuccess ? 0 : 1);
                $state['current_sequence'] = $challenge->sequence + 1;
                $state['challenge_started_at'] = now()->toIso8601String();
                $session->update(['state' => $state]);

                if ($state['current_sequence'] > $session->challenges()->count()) {
                    $this->finalize($session);
                }
            }

            return [
                ...$this->state($session),
                'feedback' => $this->feedbackManager->forAnswer($isSuccess),
                'attempt' => [
                    'number' => $attemptNumber,
                    'maximum' => $maximumAttempts,
                    'advanced' => $shouldAdvance,
                    'event_id' => $event->id,
                ],
                'is_replay' => false,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function state(SimulationSession $simulationSession): array
    {
        $simulationSession->refresh()->loadMissing(['simulation', 'result']);
        $state = $simulationSession->state ?? [];
        $currentSequence = (int) data_get($state, 'current_sequence', 1);
        $challenge = $simulationSession->challenges()
            ->where('sequence', $currentSequence)
            ->first();
        $challengeCount = $simulationSession->challenges()->count();
        $maximumAttempts = max(1, min(
            5,
            (int) data_get($simulationSession->simulation->configuration, 'max_attempts', 3),
        ));

        return [
            'session' => [
                'status' => $simulationSession->status,
                'difficulty' => $simulationSession->difficulty,
            ],
            'simulation' => [
                'code' => $simulationSession->simulation->code,
                'title' => $simulationSession->simulation->title,
                'title_marathi' => $simulationSession->simulation->title_marathi,
                'engine_key' => $simulationSession->simulation->engine_key,
            ],
            'stats' => [
                'score' => (int) data_get($state, 'score', 0),
                'successful_count' => (int) data_get($state, 'successful_count', 0),
                'unsuccessful_count' => (int) data_get($state, 'unsuccessful_count', 0),
                'completed_count' => min($challengeCount, max(0, $currentSequence - 1)),
                'challenge_count' => $challengeCount,
            ],
            'challenge' => $challenge === null ? null : [
                'id' => $challenge->id,
                'sequence' => $challenge->sequence,
                'prompt' => $challenge->prompt,
                'prompt_marathi' => $challenge->prompt_marathi,
                'interaction' => $challenge->interaction,
                'attempts_used' => $challenge->events()->count(),
                'maximum_attempts' => $maximumAttempts,
            ],
            'completed' => $simulationSession->status === 'completed',
            'result' => $simulationSession->result === null ? null : [
                'score' => $simulationSession->result->score,
                'max_score' => $simulationSession->result->max_score,
                'accuracy' => (float) $simulationSession->result->accuracy,
                'xp_awarded' => $simulationSession->result->xp_awarded,
            ],
        ];
    }

    private function primarySkill(Simulation $simulation): Skill
    {
        $skill = $simulation->skills
            ->sortByDesc(fn (Skill $candidate): float => (float) $candidate->pivot->weight)
            ->first();
        abort_if($skill === null, 422, 'This simulation is not connected to a skill.');

        return $skill;
    }

    /**
     * @param  array<string, mixed>  $expectedState
     * @param  array<string, mixed>  $submittedState
     * @return array<string, int|array<int, string>>
     */
    private function canonicalState(array $expectedState, array $submittedState): array
    {
        return collect($expectedState)
            ->mapWithKeys(function (mixed $expected, string $key) use ($submittedState): array {
                if (is_array($expected)) {
                    return [$key => collect($submittedState[$key] ?? [])->map(
                        fn (mixed $value): string => trim((string) $value),
                    )->values()->all()];
                }

                return [$key => (int) ($submittedState[$key] ?? 0)];
            })
            ->all();
    }

    private function finalize(SimulationSession $simulationSession): SimulationResult
    {
        $simulationSession->loadMissing([
            'academicYear',
            'simulation',
            'student.school',
            'challenges.events',
        ]);
        $existingResult = $simulationSession->result()->first();

        if ($existingResult !== null) {
            return $existingResult;
        }

        $challengeCount = $simulationSession->challenges->count();
        $successfulCount = $simulationSession->challenges
            ->filter(fn (SimulationChallenge $challenge): bool => $challenge->events->contains('is_success', true))
            ->count();
        $score = (int) $simulationSession->events()->where('is_success', true)->sum('score');
        $maxScore = (int) $simulationSession->challenges->sum('max_score');
        $accuracy = $challengeCount === 0 ? 0 : round(($successfulCount / $challengeCount) * 100, 2);
        $completedAt = now();
        $durationSeconds = max(1, (int) $simulationSession->started_at->diffInSeconds($completedAt));
        $previousBest = SimulationResult::query()
            ->whereHas('session', fn ($query) => $query
                ->where('student_id', $simulationSession->student_id)
                ->where('simulation_id', $simulationSession->simulation_id))
            ->max('accuracy');
        $gamification = $this->gamificationService->recordCompletion(
            $simulationSession->student,
            $simulationSession->academicYear,
            'simulation_session',
            $simulationSession->id,
            $simulationSession->challenges->first()?->skill_id,
            $accuracy,
            $durationSeconds,
            $previousBest !== null && $accuracy > (float) $previousBest,
        );
        $xpAwarded = $gamification['xp_awarded'];
        $this->recordSkillEvidence($simulationSession, $xpAwarded);
        $this->updateSkillProgress($simulationSession);
        $result = $simulationSession->result()->create([
            'score' => $score,
            'max_score' => $maxScore,
            'successful_count' => $successfulCount,
            'unsuccessful_count' => max(0, $challengeCount - $successfulCount),
            'accuracy' => $accuracy,
            'duration_seconds' => $durationSeconds,
            'xp_awarded' => $xpAwarded,
            'result_payload' => [
                'difficulty' => $simulationSession->difficulty,
                'event_count' => $simulationSession->events()->count(),
                'attempts_per_challenge' => $simulationSession->challenges
                    ->mapWithKeys(fn (SimulationChallenge $challenge): array => [
                        (string) $challenge->id => $challenge->events->count(),
                    ])
                    ->all(),
            ],
            'validated_at' => $completedAt,
        ]);
        $simulationSession->update([
            'status' => 'completed',
            'completed_at' => $completedAt,
            'duration_seconds' => $durationSeconds,
            'score' => $score,
            'accuracy' => $accuracy,
            'learning_events' => [
                'event_count' => $simulationSession->events()->count(),
                'successful_count' => $successfulCount,
            ],
        ]);

        return $result;
    }

    private function recordSkillEvidence(SimulationSession $simulationSession, int $xpAwarded): void
    {
        foreach ($simulationSession->challenges as $index => $challenge) {
            $event = $challenge->events->firstWhere('is_success', true) ?? $challenge->events->last();
            StudentSkillEvent::query()->create([
                'event_key' => (string) Str::uuid(),
                'student_id' => $simulationSession->student_id,
                'skill_id' => $challenge->skill_id,
                'academic_year_id' => $simulationSession->academic_year_id,
                'activity_type' => 'simulation',
                'source_type' => 'simulation_session',
                'source_id' => $simulationSession->id,
                'score' => $event?->score ?? 0,
                'max_score' => $challenge->max_score,
                'accuracy' => $event?->is_success ? 100 : 0,
                'duration_seconds' => $event === null ? null : max(1, (int) ceil($event->response_ms / 1000)),
                'difficulty' => $simulationSession->difficulty,
                'is_correct' => $event?->is_success ?? false,
                'xp_awarded' => $index === 0 ? $xpAwarded : 0,
                'metadata' => [
                    'simulation_id' => $simulationSession->simulation_id,
                    'simulation_challenge_id' => $challenge->id,
                    'simulation_event_id' => $event?->id,
                    'attempt_count' => $challenge->events->count(),
                    'interaction_type' => data_get($challenge->interaction, 'type'),
                ],
                'occurred_at' => $event?->occurred_at ?? $simulationSession->completed_at ?? now(),
            ]);
        }
    }

    private function updateSkillProgress(SimulationSession $simulationSession): void
    {
        foreach ($simulationSession->challenges->groupBy('skill_id') as $skillId => $challenges) {
            $challengeCount = $challenges->count();
            $successfulCount = $challenges->filter(
                fn (SimulationChallenge $challenge): bool => $challenge->events->contains('is_success', true),
            )->count();
            $accuracy = round(($successfulCount / $challengeCount) * 100, 2);
            $progress = StudentSkillProgress::query()->firstOrCreate([
                'student_id' => $simulationSession->student_id,
                'skill_id' => $skillId,
                'academic_year_id' => $simulationSession->academic_year_id,
            ]);
            $progress = StudentSkillProgress::query()
                ->whereKey($progress->id)
                ->lockForUpdate()
                ->firstOrFail();
            $totalAttempts = $progress->total_attempts + $challengeCount;
            $totalCorrect = $progress->correct_attempts + $successfulCount;
            $simulationCount = $progress->simulation_count + 1;
            $cumulativeAccuracy = round(($totalCorrect / $totalAttempts) * 100, 2);
            $averageScore = round(
                (((float) $progress->average_score * $progress->simulation_count) + $accuracy) / $simulationCount,
                2,
            );
            $currentLevel = $this->difficultyManager->nextLevel(
                $progress->current_level,
                $accuracy,
                $simulationSession->simulation->configuration ?? [],
            );

            $progress->update([
                'current_level' => $currentLevel,
                'mastery_score' => $cumulativeAccuracy,
                'total_attempts' => $totalAttempts,
                'correct_attempts' => $totalCorrect,
                'accuracy' => $cumulativeAccuracy,
                'best_score' => max((float) $progress->best_score, $accuracy),
                'average_score' => $averageScore,
                'simulation_count' => $simulationCount,
                'last_activity_at' => now(),
                'status' => $cumulativeAccuracy >= 80 ? 'mastered' : 'in_progress',
            ]);
        }
    }
}
