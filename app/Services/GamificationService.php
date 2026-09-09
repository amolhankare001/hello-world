<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\Badge;
use App\Models\DailyGoal;
use App\Models\Streak;
use App\Models\Student;
use App\Models\StudentBadge;
use App\Models\XpTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class GamificationService
{
    /**
     * @return array{
     *     xp_awarded: int,
     *     streak: Streak,
     *     daily_goal: DailyGoal,
     *     badges: list<Badge>,
     *     is_replay: bool
     * }
     */
    public function recordCompletion(
        Student $student,
        AcademicYear $academicYear,
        string $sourceType,
        int $sourceId,
        ?int $skillId,
        float $accuracy,
        int $durationSeconds,
        bool $isPersonalBest = false,
    ): array {
        $completionPoints = config("gamification.xp.completion.{$sourceType}");

        if (! is_int($completionPoints)) {
            throw new InvalidArgumentException("Unsupported gamification source [{$sourceType}].");
        }

        return DB::transaction(function () use (
            $student,
            $academicYear,
            $sourceType,
            $sourceId,
            $skillId,
            $accuracy,
            $durationSeconds,
            $isPersonalBest,
            $completionPoints,
        ): array {
            $lockedStudent = Student::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();
            $lockedStudent->loadMissing('school:id,timezone');
            [$completion, $isNewCompletion] = $this->award(
                $lockedStudent,
                $academicYear,
                $sourceType,
                $sourceId,
                $skillId,
                $completionPoints,
                str_replace('_attempt', '', str_replace('_session', '', $sourceType)).'_completed',
                ['category' => 'completion', 'accuracy' => $accuracy],
            );

            if (! $isNewCompletion) {
                return $this->replayResult($lockedStudent, $academicYear);
            }

            $xpAwarded = $completion->points;

            if ($accuracy >= (float) config('gamification.xp.accuracy_bonus_threshold', 80)) {
                [$accuracyBonus] = $this->award(
                    $lockedStudent,
                    $academicYear,
                    $sourceType,
                    $sourceId,
                    $skillId,
                    (int) config('gamification.xp.accuracy_bonus', 20),
                    'accuracy_bonus',
                    ['category' => 'bonus', 'accuracy' => $accuracy],
                );
                $xpAwarded += $accuracyBonus->points;
            }

            if ($isPersonalBest) {
                [$personalBestBonus] = $this->award(
                    $lockedStudent,
                    $academicYear,
                    $sourceType,
                    $sourceId,
                    $skillId,
                    (int) config('gamification.xp.personal_best', 25),
                    'personal_best',
                    ['category' => 'bonus'],
                );
                $xpAwarded += $personalBestBonus->points;
                $this->achievement(
                    $lockedStudent,
                    $academicYear,
                    "personal-best-{$sourceType}-{$sourceId}",
                    'Personal best',
                    'वैयक्तिक सर्वोत्तम',
                    ['source_type' => $sourceType, 'source_id' => $sourceId],
                );
            }

            $streak = $this->updateStreak($lockedStudent, $academicYear);
            $this->achievement(
                $lockedStudent,
                $academicYear,
                'first-learning-activity',
                'First learning activity',
                'पहिली अध्ययन कृती',
                ['source_type' => $sourceType, 'source_id' => $sourceId],
            );
            $this->recordStreakAchievements($lockedStudent, $academicYear, $streak);
            [$badges, $badgeXp] = $this->awardEligibleBadges($lockedStudent, $academicYear, $streak);
            $xpAwarded += $badgeXp;
            [$dailyGoal, $dailyGoalXp] = $this->updateDailyGoal(
                $lockedStudent,
                $academicYear,
                $sourceType,
                $sourceId,
                $durationSeconds,
                $xpAwarded,
            );

            return [
                'xp_awarded' => $xpAwarded + $dailyGoalXp,
                'streak' => $streak,
                'daily_goal' => $dailyGoal,
                'badges' => $badges,
                'is_replay' => false,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array{XpTransaction, bool}
     */
    private function award(
        Student $student,
        AcademicYear $academicYear,
        string $sourceType,
        int $sourceId,
        ?int $skillId,
        int $points,
        string $reason,
        array $metadata,
    ): array {
        $transaction = XpTransaction::query()->firstOrCreate(
            [
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'reason' => $reason,
            ],
            [
                'transaction_key' => (string) Str::uuid(),
                'skill_id' => $skillId,
                'points' => max(0, $points),
                'metadata' => $metadata,
                'awarded_at' => now(),
            ],
        );

        return [$transaction, $transaction->wasRecentlyCreated];
    }

    /**
     * @return array{
     *     xp_awarded: int,
     *     streak: Streak,
     *     daily_goal: DailyGoal,
     *     badges: list<Badge>,
     *     is_replay: bool
     * }
     */
    private function replayResult(
        Student $student,
        AcademicYear $academicYear,
    ): array {
        $timezone = $student->school?->timezone ?? config('app.timezone');
        $goalDate = now($timezone)->toDateString();
        $streak = Streak::query()->firstOrCreate(
            ['student_id' => $student->id, 'academic_year_id' => $academicYear->id],
            ['available_freezes' => (int) config('gamification.streak.initial_freezes', 0)],
        );
        $dailyGoal = DailyGoal::query()
            ->where('student_id', $student->id)
            ->whereDate('goal_date', $goalDate)
            ->first();

        if ($dailyGoal === null) {
            $dailyGoal = DailyGoal::query()->create([
                'student_id' => $student->id,
                'goal_date' => $goalDate,
                'academic_year_id' => $academicYear->id,
                'target_activities' => (int) config('gamification.daily_goal.activities', 3),
                'target_minutes' => (int) config('gamification.daily_goal.minutes', 15),
                'target_xp' => (int) config('gamification.daily_goal.xp', 50),
            ]);
        }

        return [
            'xp_awarded' => 0,
            'streak' => $streak,
            'daily_goal' => $dailyGoal,
            'badges' => [],
            'is_replay' => true,
        ];
    }

    private function updateStreak(Student $student, AcademicYear $academicYear): Streak
    {
        $timezone = $student->school?->timezone ?? config('app.timezone');
        $activityDate = now($timezone)->startOfDay();
        $streak = Streak::query()->firstOrCreate(
            ['student_id' => $student->id, 'academic_year_id' => $academicYear->id],
            ['available_freezes' => (int) config('gamification.streak.initial_freezes', 0)],
        );
        $streak = Streak::query()->whereKey($streak->id)->lockForUpdate()->firstOrFail();

        if ($streak->last_activity_on?->isSameDay($activityDate)) {
            return $streak;
        }

        $currentDays = 1;
        $availableFreezes = $streak->available_freezes;

        if ($streak->last_activity_on?->copy()->addDay()->isSameDay($activityDate)) {
            $currentDays = $streak->current_days + 1;
        } elseif (
            $streak->last_activity_on !== null
            && $availableFreezes > 0
            && $streak->last_activity_on->diffInDays($activityDate)
                <= (int) config('gamification.streak.freeze_max_gap_days', 2)
        ) {
            $currentDays = $streak->current_days + 1;
            $availableFreezes--;
        }

        $streak->update([
            'current_days' => $currentDays,
            'longest_days' => max($streak->longest_days, $currentDays),
            'last_activity_on' => $activityDate,
            'available_freezes' => $availableFreezes,
        ]);

        return $streak;
    }

    private function recordStreakAchievements(
        Student $student,
        AcademicYear $academicYear,
        Streak $streak,
    ): void {
        foreach ((array) config('gamification.streak.milestones', []) as $milestone) {
            if ($streak->current_days < $milestone) {
                continue;
            }

            $this->achievement(
                $student,
                $academicYear,
                "streak-{$milestone}",
                "{$milestone}-day learning streak",
                "सलग {$milestone} दिवस अध्ययन",
                ['streak_days' => $milestone],
            );
        }
    }

    /**
     * @return array{list<Badge>, int}
     */
    private function awardEligibleBadges(
        Student $student,
        AcademicYear $academicYear,
        Streak $streak,
    ): array {
        $earnedBadgeIds = StudentBadge::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->pluck('badge_id');
        $badges = Badge::query()
            ->where('is_active', true)
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('school_id')
                ->orWhere('school_id', $student->school_id))
            ->whereNotIn('id', $earnedBadgeIds)
            ->orderBy('id')
            ->get();
        $badgeData = $this->badgeData($student, $academicYear, $streak);
        $awardedBadges = [];
        $xpAwarded = 0;

        foreach ($badges as $badge) {
            $metrics = $this->badgeMetrics($badge, $badgeData);

            if (! $this->badgeCriteriaMet($badge->criteria ?? [], $metrics)) {
                continue;
            }

            $studentBadge = StudentBadge::query()->firstOrCreate(
                [
                    'student_id' => $student->id,
                    'badge_id' => $badge->id,
                    'academic_year_id' => $academicYear->id,
                ],
                [
                    'earned_at' => now(),
                    'evidence' => $metrics,
                ],
            );

            if (! $studentBadge->wasRecentlyCreated) {
                continue;
            }

            [$bonus] = $this->award(
                $student,
                $academicYear,
                'badge',
                $badge->id,
                null,
                $badge->xp_bonus,
                'badge_bonus',
                ['category' => 'badge', 'badge_code' => $badge->code],
            );
            $xpAwarded += $bonus->points;
            $awardedBadges[] = $badge;
        }

        return [$awardedBadges, $xpAwarded];
    }

    /**
     * @return array{
     *     completions: Collection<int, XpTransaction>,
     *     xp: int,
     *     streak_days: int
     * }
     */
    private function badgeData(Student $student, AcademicYear $academicYear, Streak $streak): array
    {
        $completions = XpTransaction::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('metadata->category', 'completion')
            ->with('skill:id,subject_id')
            ->get(['id', 'skill_id', 'source_type']);

        return [
            'completions' => $completions,
            'xp' => (int) XpTransaction::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $academicYear->id)
                ->sum('points'),
            'streak_days' => $streak->current_days,
        ];
    }

    /**
     * @param  array{
     *     completions: Collection<int, XpTransaction>,
     *     xp: int,
     *     streak_days: int
     * }  $badgeData
     * @return array<string, int>
     */
    private function badgeMetrics(Badge $badge, array $badgeData): array
    {
        $completions = $badgeData['completions']
            ->when(
                $badge->skill_id !== null,
                fn (Collection $items): Collection => $items->where('skill_id', $badge->skill_id),
            )
            ->when(
                $badge->skill_id === null && $badge->subject_id !== null,
                fn (Collection $items): Collection => $items->filter(
                    fn (XpTransaction $transaction): bool => $transaction->skill?->subject_id === $badge->subject_id,
                ),
            );

        return [
            'activity_count' => $completions->count(),
            'practice_count' => $completions->where('source_type', 'practice_attempt')->count(),
            'assessment_count' => $completions->where('source_type', 'test_attempt')->count(),
            'game_count' => $completions->where('source_type', 'game_session')->count(),
            'simulation_count' => $completions->where('source_type', 'simulation_session')->count(),
            'xp' => $badgeData['xp'],
            'streak_days' => $badgeData['streak_days'],
        ];
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @param  array<string, int>  $metrics
     */
    private function badgeCriteriaMet(array $criteria, array $metrics): bool
    {
        foreach (['activity_count', 'practice_count', 'assessment_count', 'game_count', 'simulation_count', 'xp', 'streak_days'] as $metric) {
            if (isset($criteria[$metric]) && $metrics[$metric] < (int) $criteria[$metric]) {
                return false;
            }
        }

        return $criteria !== [];
    }

    /**
     * @return array{DailyGoal, int}
     */
    private function updateDailyGoal(
        Student $student,
        AcademicYear $academicYear,
        string $sourceType,
        int $sourceId,
        int $durationSeconds,
        int $earnedXp,
    ): array {
        $timezone = $student->school?->timezone ?? config('app.timezone');
        $goalDate = now($timezone)->toDateString();
        $dailyGoal = DailyGoal::query()
            ->where('student_id', $student->id)
            ->whereDate('goal_date', $goalDate)
            ->first();

        if ($dailyGoal === null) {
            $dailyGoal = DailyGoal::query()->create([
                'student_id' => $student->id,
                'goal_date' => $goalDate,
                'academic_year_id' => $academicYear->id,
                'target_activities' => (int) config('gamification.daily_goal.activities', 3),
                'target_minutes' => (int) config('gamification.daily_goal.minutes', 15),
                'target_xp' => (int) config('gamification.daily_goal.xp', 50),
            ]);
        }
        $dailyGoal = DailyGoal::query()->whereKey($dailyGoal->id)->lockForUpdate()->firstOrFail();
        $dailyGoal->completed_activities++;
        $dailyGoal->completed_minutes += max(1, (int) ceil($durationSeconds / 60));
        $dailyGoal->earned_xp += $earnedXp;

        $goalReached = $dailyGoal->completed_activities >= $dailyGoal->target_activities
            || ($dailyGoal->target_minutes !== null && $dailyGoal->completed_minutes >= $dailyGoal->target_minutes)
            || ($dailyGoal->target_xp !== null && $dailyGoal->earned_xp >= $dailyGoal->target_xp);
        $dailyGoalXp = 0;

        if ($goalReached && $dailyGoal->completed_at === null) {
            $dailyGoal->completed_at = now();
            [$goalBonus] = $this->award(
                $student,
                $academicYear,
                'daily_goal',
                $dailyGoal->id,
                null,
                (int) config('gamification.xp.daily_goal', 30),
                'daily_goal_completed',
                ['category' => 'daily_goal', 'goal_date' => $goalDate],
            );
            $dailyGoalXp = $goalBonus->points;
            $dailyGoal->earned_xp += $dailyGoalXp;
            $this->achievement(
                $student,
                $academicYear,
                "daily-goal-{$goalDate}",
                'Daily goal completed',
                'आजचे लक्ष्य पूर्ण',
                ['goal_date' => $goalDate, 'source_type' => $sourceType, 'source_id' => $sourceId],
            );
        }

        $dailyGoal->save();

        return [$dailyGoal, $dailyGoalXp];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function achievement(
        Student $student,
        AcademicYear $academicYear,
        string $key,
        string $title,
        string $titleMarathi,
        array $metadata,
    ): Achievement {
        return Achievement::query()->firstOrCreate(
            [
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'achievement_key' => $key,
            ],
            [
                'title' => $title,
                'title_marathi' => $titleMarathi,
                'metadata' => $metadata,
                'achieved_at' => now(),
            ],
        );
    }
}
