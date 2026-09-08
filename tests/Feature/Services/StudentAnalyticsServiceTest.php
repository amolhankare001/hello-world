<?php

namespace Tests\Feature\Services;

use App\Models\AcademicYear;
use App\Models\ErrorType;
use App\Models\RecommendationRule;
use App\Models\School;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentSkillEvent;
use App\Models\StudentSkillProgress;
use App\Models\Subject;
use App\Services\StudentAnalyticsService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentAnalyticsServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_calculates_separate_evidence_metrics_repeated_errors_and_recent_trend(): void
    {
        [$student, $academicYear, $subject] = $this->learningContext();
        $skill = Skill::factory()->for($subject)->create(['code' => 'DECLINING_SKILL']);
        $errorType = ErrorType::factory()->for($skill)->create([
            'name_marathi' => 'बेरीज चूक',
        ]);
        StudentSkillProgress::query()->create([
            'student_id' => $student->id,
            'skill_id' => $skill->id,
            'academic_year_id' => $academicYear->id,
            'mastery_score' => 45,
            'accuracy' => 55,
            'improvement' => 8,
            'practice_count' => 4,
            'game_count' => 2,
            'simulation_count' => 1,
            'last_activity_at' => now(),
        ]);
        $activityTypes = [
            'practice', 'practice', 'game', 'simulation', 'assessment',
            'practice', 'game', 'simulation', 'assessment', 'practice',
        ];

        foreach ([80, 80, 80, 80, 80, 40, 40, 40, 40, 40] as $index => $accuracy) {
            StudentSkillEvent::factory()->for($student)->for($skill)->for($academicYear)->create([
                'activity_type' => $activityTypes[$index],
                'accuracy' => $accuracy,
                'is_correct' => $index >= 3,
                'error_type_id' => $index < 3 ? $errorType->id : null,
                'occurred_at' => now()->subDays(10 - $index),
            ]);
        }
        $this->rule('RED_REPEATED_ERROR', 'repeated_errors', 'gte', 3, 'red', 3, 10);

        $analysis = app(StudentAnalyticsService::class)
            ->analyze($student, $academicYear)
            ->firstWhere('skill.id', $skill->id);

        $this->assertNotNull($analysis);
        $this->assertSame('red', $analysis['risk_level']);
        $this->assertSame('RED_REPEATED_ERROR', $analysis['rule']->code);
        $this->assertSame(3, $analysis['metrics']['repeated_errors']);
        $this->assertSame('बेरीज चूक', $analysis['metrics']['repeated_error']['name_marathi']);
        $this->assertSame(-40.0, $analysis['metrics']['recent_trend']);
        $this->assertSame(60.0, $analysis['metrics']['practice_performance']);
        $this->assertSame(60.0, $analysis['metrics']['game_performance']);
        $this->assertSame(60.0, $analysis['metrics']['simulation_performance']);
        $this->assertSame(60.0, $analysis['metrics']['assessment_performance']);
        $this->assertSame(4, $analysis['metrics']['practice_count']);
        $this->assertSame(10, $analysis['metrics']['event_count']);
    }

    public function test_database_rules_classify_green_yellow_and_red_skills(): void
    {
        [$student, $academicYear, $subject] = $this->learningContext();
        $redSkill = $this->skillWithEvidence($student, $academicYear, $subject, 'RED_SKILL', 45, 45, 3);
        $yellowSkill = $this->skillWithEvidence($student, $academicYear, $subject, 'YELLOW_SKILL', 65, 65, 2);
        $greenSkill = $this->skillWithEvidence($student, $academicYear, $subject, 'GREEN_SKILL', 85, 85, 2);
        $this->rule('RED_LOW_ACCURACY', 'accuracy', 'lt', 50, 'red', 3, 10);
        $this->rule('YELLOW_LOW_ACCURACY', 'accuracy', 'lt', 70, 'yellow', 2, 20);

        $analyses = app(StudentAnalyticsService::class)
            ->analyze($student, $academicYear)
            ->keyBy(fn (array $analysis): int => $analysis['skill']->id);

        $this->assertSame('red', $analyses[$redSkill->id]['risk_level']);
        $this->assertSame('yellow', $analyses[$yellowSkill->id]['risk_level']);
        $this->assertSame('green', $analyses[$greenSkill->id]['risk_level']);
    }

    /**
     * @return array{Student, AcademicYear, Subject}
     */
    private function learningContext(): array
    {
        $school = School::factory()->create();
        $student = Student::factory()->for($school)->create();
        $academicYear = AcademicYear::factory()->for($school)->create();
        $subject = Subject::factory()->for($school)->create();

        return [$student, $academicYear, $subject];
    }

    private function skillWithEvidence(
        Student $student,
        AcademicYear $academicYear,
        Subject $subject,
        string $code,
        int $mastery,
        int $accuracy,
        int $eventCount,
    ): Skill {
        $skill = Skill::factory()->for($subject)->create(['code' => $code]);
        StudentSkillProgress::query()->create([
            'student_id' => $student->id,
            'skill_id' => $skill->id,
            'academic_year_id' => $academicYear->id,
            'mastery_score' => $mastery,
            'accuracy' => $accuracy,
        ]);
        StudentSkillEvent::factory()
            ->count($eventCount)
            ->for($student)
            ->for($skill)
            ->for($academicYear)
            ->create(['accuracy' => $accuracy]);

        return $skill;
    }

    private function rule(
        string $code,
        string $signal,
        string $operator,
        int $threshold,
        string $riskLevel,
        int $minimumEvents,
        int $sortOrder,
    ): RecommendationRule {
        return RecommendationRule::factory()->create([
            'code' => $code,
            'signal' => $signal,
            'operator' => $operator,
            'threshold' => $threshold,
            'risk_level' => $riskLevel,
            'minimum_events' => $minimumEvents,
            'sort_order' => $sortOrder,
        ]);
    }
}
