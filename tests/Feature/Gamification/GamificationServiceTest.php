<?php

namespace Tests\Feature\Gamification;

use App\Models\AcademicYear;
use App\Models\Badge;
use App\Models\DailyGoal;
use App\Models\School;
use App\Models\Skill;
use App\Models\Streak;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\GamificationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class GamificationServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_completion_awards_configured_xp_once_with_accuracy_bonus(): void
    {
        [$student, $academicYear] = $this->studentContext();
        $this->disableDailyGoalCompletion();

        $result = $this->service()->recordCompletion(
            $student,
            $academicYear,
            'practice_attempt',
            101,
            null,
            85,
            60,
        );
        $replay = $this->service()->recordCompletion(
            $student,
            $academicYear,
            'practice_attempt',
            101,
            null,
            85,
            60,
        );

        $this->assertSame(35, $result['xp_awarded']);
        $this->assertFalse($result['is_replay']);
        $this->assertSame(0, $replay['xp_awarded']);
        $this->assertTrue($replay['is_replay']);
        $this->assertDatabaseCount('xp_transactions', 2);
        $this->assertDatabaseHas('xp_transactions', [
            'student_id' => $student->id,
            'source_type' => 'practice_attempt',
            'source_id' => 101,
            'reason' => 'practice_completed',
            'points' => 15,
        ]);
        $this->assertDatabaseHas('xp_transactions', [
            'student_id' => $student->id,
            'reason' => 'accuracy_bonus',
            'points' => 20,
        ]);
    }

    public function test_personal_best_awards_bonus_and_records_achievement(): void
    {
        [$student, $academicYear] = $this->studentContext();
        $this->disableDailyGoalCompletion();

        $result = $this->service()->recordCompletion(
            $student,
            $academicYear,
            'test_attempt',
            201,
            null,
            60,
            120,
            true,
        );

        $this->assertSame(55, $result['xp_awarded']);
        $this->assertDatabaseHas('xp_transactions', [
            'student_id' => $student->id,
            'reason' => 'personal_best',
            'points' => 25,
        ]);
        $this->assertDatabaseHas('achievements', [
            'student_id' => $student->id,
            'achievement_key' => 'personal-best-test_attempt-201',
            'title' => 'Personal best',
        ]);
    }

    public function test_streak_increments_uses_a_freeze_and_resets_after_a_longer_gap(): void
    {
        [$student, $academicYear] = $this->studentContext();
        Streak::factory()->for($student)->for($academicYear)->create(['available_freezes' => 1]);
        $this->disableDailyGoalCompletion();
        $this->travelTo(CarbonImmutable::parse('2026-09-08 10:00:00', 'Asia/Kolkata'));

        $first = $this->complete($student, $academicYear, 1);
        $this->travelTo(CarbonImmutable::parse('2026-09-09 10:00:00', 'Asia/Kolkata'));
        $second = $this->complete($student, $academicYear, 2);
        $this->travelTo(CarbonImmutable::parse('2026-09-11 10:00:00', 'Asia/Kolkata'));
        $frozen = $this->complete($student, $academicYear, 3);
        $this->travelTo(CarbonImmutable::parse('2026-09-14 10:00:00', 'Asia/Kolkata'));
        $reset = $this->complete($student, $academicYear, 4);

        $this->assertSame(1, $first['streak']->current_days);
        $this->assertSame(2, $second['streak']->current_days);
        $this->assertSame(3, $frozen['streak']->current_days);
        $this->assertSame(0, $frozen['streak']->available_freezes);
        $this->assertSame(1, $reset['streak']->current_days);
        $this->assertSame(3, $reset['streak']->longest_days);
        $this->assertDatabaseHas('achievements', [
            'student_id' => $student->id,
            'achievement_key' => 'streak-3',
            'title' => '3-day learning streak',
        ]);
    }

    public function test_daily_goal_and_eligible_badge_are_awarded_once(): void
    {
        [$student, $academicYear] = $this->studentContext();
        $this->travelTo(CarbonImmutable::parse('2026-09-08 10:00:00', 'Asia/Kolkata'));
        config([
            'gamification.daily_goal.activities' => 2,
            'gamification.daily_goal.minutes' => 999,
            'gamification.daily_goal.xp' => 999,
        ]);
        $badge = Badge::factory()->create([
            'criteria' => ['practice_count' => 2],
            'xp_bonus' => 7,
        ]);

        $this->complete($student, $academicYear, 301);
        $result = $this->complete($student, $academicYear, 302);

        $this->assertSame(52, $result['xp_awarded']);
        $this->assertSame([$badge->id], collect($result['badges'])->pluck('id')->all());
        $this->assertNotNull($result['daily_goal']->completed_at);
        $this->assertDatabaseHas('student_badges', [
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'badge_id' => $badge->id,
        ]);
        $this->assertDatabaseHas('xp_transactions', [
            'student_id' => $student->id,
            'source_type' => 'badge',
            'source_id' => $badge->id,
            'reason' => 'badge_bonus',
            'points' => 7,
        ]);
        $this->assertDatabaseHas('xp_transactions', [
            'student_id' => $student->id,
            'source_type' => 'daily_goal',
            'reason' => 'daily_goal_completed',
            'points' => 30,
        ]);
        $this->assertDatabaseHas('achievements', [
            'student_id' => $student->id,
            'achievement_key' => 'daily-goal-2026-09-08',
        ]);
        $this->assertSame(67, $student->xpTransactions()->sum('points'));
        $this->assertSame(1, DailyGoal::query()->whereBelongsTo($student)->count());
    }

    public function test_subject_badge_counts_only_completion_evidence_for_that_subject(): void
    {
        [$student, $academicYear] = $this->studentContext();
        $this->disableDailyGoalCompletion();
        $marathi = Subject::factory()->create();
        $mathematics = Subject::factory()->create();
        $marathiSkill = Skill::factory()->for($marathi)->create();
        $mathematicsSkill = Skill::factory()->for($mathematics)->create();
        $badge = Badge::factory()->for($marathi)->create([
            'criteria' => ['practice_count' => 1],
        ]);

        $otherSubjectResult = $this->service()->recordCompletion(
            $student,
            $academicYear,
            'practice_attempt',
            401,
            $mathematicsSkill->id,
            50,
            60,
        );
        $matchingSubjectResult = $this->service()->recordCompletion(
            $student,
            $academicYear,
            'practice_attempt',
            402,
            $marathiSkill->id,
            50,
            60,
        );

        $this->assertSame([], $otherSubjectResult['badges']);
        $this->assertSame([$badge->id], collect($matchingSubjectResult['badges'])->pluck('id')->all());
    }

    /**
     * @return array{Student, AcademicYear}
     */
    private function studentContext(): array
    {
        $school = School::factory()->create();
        $student = Student::factory()
            ->for(User::factory()->for($school))
            ->for($school)
            ->create();
        $academicYear = AcademicYear::factory()->for($school)->create();

        return [$student, $academicYear];
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
    private function complete(Student $student, AcademicYear $academicYear, int $sourceId): array
    {
        return $this->service()->recordCompletion(
            $student,
            $academicYear,
            'practice_attempt',
            $sourceId,
            null,
            50,
            60,
        );
    }

    private function disableDailyGoalCompletion(): void
    {
        config([
            'gamification.daily_goal.activities' => 99,
            'gamification.daily_goal.minutes' => 999,
            'gamification.daily_goal.xp' => 999,
        ]);
    }

    private function service(): GamificationService
    {
        return app(GamificationService::class);
    }
}
