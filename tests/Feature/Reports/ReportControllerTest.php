<?php

namespace Tests\Feature\Reports;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\School;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentSkillEvent;
use App\Models\StudentSkillProgress;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_school_administrator_can_open_report_center(): void
    {
        [$user] = $this->schoolAdministrator();

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('विद्यार्थी प्रगती अहवाल')
            ->assertSee('मार्गदर्शक हस्तक्षेप अहवाल');
    }

    #[DataProvider('reportTypes')]
    public function test_each_report_type_renders_as_printable_a4_output(string $reportType): void
    {
        [$user] = $this->schoolAdministrator();

        $this->actingAs($user)
            ->get(route('reports.show', $reportType))
            ->assertOk()
            ->assertSee('@page { size: A4', false)
            ->assertSee('प्रिंट / PDF');
    }

    public function test_report_only_includes_students_from_the_administrators_school(): void
    {
        [$user, $school] = $this->schoolAdministrator();
        $academicYear = AcademicYear::factory()->for($school)->create();
        $subject = Subject::factory()->create(['name_marathi' => 'गणित']);
        $skill = Skill::factory()->for($subject)->create(['name_marathi' => 'बेरीज']);
        $student = $this->student($school, 'स्वतःचा विद्यार्थी');
        $otherStudent = $this->student(School::factory()->create(), 'इतर शाळेचा विद्यार्थी');
        StudentSkillProgress::query()->create([
            'student_id' => $student->id,
            'skill_id' => $skill->id,
            'academic_year_id' => $academicYear->id,
            'mastery_score' => 70,
            'total_attempts' => 10,
            'correct_attempts' => 8,
            'accuracy' => 80,
            'status' => 'progressing',
        ]);
        StudentSkillProgress::query()->create([
            'student_id' => $otherStudent->id,
            'skill_id' => $skill->id,
            'academic_year_id' => $academicYear->id,
            'mastery_score' => 90,
            'total_attempts' => 10,
            'correct_attempts' => 9,
            'accuracy' => 90,
            'status' => 'mastered',
        ]);

        $this->actingAs($user)
            ->get(route('reports.show', 'student_progress'))
            ->assertOk()
            ->assertSee('स्वतःचा विद्यार्थी')
            ->assertDontSee('इतर शाळेचा विद्यार्थी');

        $this->assertSame(1, AuditLog::query()
            ->where('user_id', $user->id)
            ->where('action', 'report.generated')
            ->count());
    }

    public function test_cross_school_student_filter_returns_not_found(): void
    {
        [$user] = $this->schoolAdministrator();
        $otherStudent = $this->student(School::factory()->create(), 'इतर विद्यार्थी');

        $this->actingAs($user)
            ->get(route('reports.show', [
                'report' => 'holistic_progress',
                'student_id' => $otherStudent->id,
            ]))
            ->assertNotFound();
    }

    public function test_subject_and_date_filters_limit_activity_evidence(): void
    {
        [$user, $school] = $this->schoolAdministrator();
        $academicYear = AcademicYear::factory()->for($school)->create();
        $student = $this->student($school, 'फिल्टर विद्यार्थी');
        $mathematics = Subject::factory()->create(['name_marathi' => 'गणित']);
        $marathi = Subject::factory()->create(['name_marathi' => 'मराठी']);
        $addition = Skill::factory()->for($mathematics)->create(['name_marathi' => 'बेरीज']);
        $reading = Skill::factory()->for($marathi)->create(['name_marathi' => 'वाचन']);
        StudentSkillEvent::factory()
            ->for($student)
            ->for($academicYear)
            ->for($addition)
            ->create([
                'activity_type' => 'game',
                'occurred_at' => '2026-09-05 10:00:00',
            ]);
        StudentSkillEvent::factory()
            ->for($student)
            ->for($academicYear)
            ->for($reading)
            ->create([
                'activity_type' => 'game',
                'occurred_at' => '2026-08-01 10:00:00',
            ]);

        $this->actingAs($user)
            ->get(route('reports.show', [
                'report' => 'game_performance',
                'subject_id' => $mathematics->id,
                'from' => '2026-09-01',
                'to' => '2026-09-30',
            ]))
            ->assertOk()
            ->assertSee('बेरीज')
            ->assertSee('05-09-2026')
            ->assertDontSee('01-08-2026');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function reportTypes(): array
    {
        return [
            'student progress' => ['student_progress'],
            'holistic progress' => ['holistic_progress'],
            'pre-test' => ['pre_test'],
            'post-test' => ['post_test'],
            'improvement' => ['pre_post_improvement'],
            'skill-wise' => ['skill_wise'],
            'game performance' => ['game_performance'],
            'practice' => ['practice'],
            'class group' => ['class_group'],
            'mentor intervention' => ['mentor_intervention'],
        ];
    }

    /**
     * @return array{User, School}
     */
    private function schoolAdministrator(): array
    {
        $school = School::factory()->create();
        $role = Role::query()->firstOrCreate(
            ['code' => RoleCode::SchoolAdmin->value],
            ['name' => 'School administrator'],
        );
        $user = User::factory()->for($school)->create(['role_id' => $role->id]);

        return [$user, $school];
    }

    private function student(School $school, string $name): Student
    {
        return Student::factory()
            ->for(User::factory()->for($school)->state(['name' => $name]))
            ->for($school)
            ->create();
    }
}
