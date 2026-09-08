<?php

namespace Tests\Feature\Dashboard;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Badge;
use App\Models\DailyGoal;
use App\Models\Division;
use App\Models\PracticeActivity;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Skill;
use App\Models\Streak;
use App\Models\Student;
use App\Models\StudentBadge;
use App\Models\StudentEnrollment;
use App\Models\StudentSkillProgress;
use App\Models\Subject;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_dashboard_renders_positive_learning_summary(): void
    {
        [$user, $student, $academicYear] = $this->studentContext('Aarav');
        $marathi = Subject::factory()->create([
            'code' => 'MARATHI',
            'name' => 'Marathi',
            'name_marathi' => 'मराठी',
            'sort_order' => 1,
        ]);
        $mathematics = Subject::factory()->create([
            'code' => 'MATHEMATICS',
            'name' => 'Mathematics',
            'name_marathi' => 'गणित',
            'sort_order' => 2,
        ]);
        $wordSkill = Skill::factory()->for($marathi)->create(['name_marathi' => 'शब्द ओळख']);
        Skill::factory()->for($mathematics)->create(['name_marathi' => 'संख्या ओळख']);
        StudentSkillProgress::query()->create([
            'student_id' => $student->id,
            'skill_id' => $wordSkill->id,
            'academic_year_id' => $academicYear->id,
            'mastery_score' => 65,
            'status' => 'in_progress',
        ]);
        $activity = Activity::factory()->for($wordSkill)->create([
            'school_id' => $student->school_id,
            'type' => 'practice',
            'title' => 'Word recognition',
            'title_marathi' => 'शब्द ओळख सराव',
            'status' => 'published',
            'published_at' => now(),
        ]);
        PracticeActivity::factory()->for($activity)->create();
        XpTransaction::factory()->for($student)->for($academicYear)->create(['points' => 120]);
        Streak::factory()->for($student)->for($academicYear)->create(['current_days' => 5]);
        DailyGoal::factory()->for($student)->for($academicYear)->create([
            'goal_date' => now('Asia/Kolkata')->toDateString(),
            'completed_activities' => 2,
            'target_activities' => 3,
        ]);
        $badge = Badge::factory()->create(['name_marathi' => 'शब्दमित्र']);
        StudentBadge::factory()->for($student)->for($academicYear)->for($badge)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('नमस्कार, Aarav!')
            ->assertSee('माझा शिकण्याचा प्रवास')
            ->assertSee('आजचे लक्ष्य')
            ->assertSee('सलग 5 दिवस')
            ->assertSee('120')
            ->assertSee('मराठी')
            ->assertSee('गणित')
            ->assertSee('शब्द ओळख सराव')
            ->assertSee('शब्दमित्र')
            ->assertDontSee('slow learner');
    }

    public function test_student_dashboard_excludes_other_school_recommendations(): void
    {
        [$user, $student] = $this->studentContext();
        $subject = Subject::factory()->create(['code' => 'MARATHI']);
        $skill = Skill::factory()->for($subject)->create();
        $visible = Activity::factory()->for($skill)->create([
            'school_id' => $student->school_id,
            'type' => 'practice',
            'title_marathi' => 'माझा शाळेचा सराव',
            'status' => 'published',
        ]);
        PracticeActivity::factory()->for($visible)->create();
        $otherSchool = School::factory()->create();
        $hidden = Activity::factory()->for($skill)->create([
            'school_id' => $otherSchool->id,
            'type' => 'practice',
            'title_marathi' => 'दुसऱ्या शाळेचा सराव',
            'status' => 'published',
        ]);
        PracticeActivity::factory()->for($hidden)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('माझा शाळेचा सराव')
            ->assertDontSee('दुसऱ्या शाळेचा सराव');
    }

    public function test_public_leaderboard_is_not_exposed(): void
    {
        [$user] = $this->studentContext();

        $this->actingAs($user)->get('/leaderboard')->assertNotFound();
    }

    /**
     * @return array{User, Student, AcademicYear}
     */
    private function studentContext(?string $name = null): array
    {
        $school = School::factory()->create();
        $role = Role::query()->firstOrCreate(
            ['code' => RoleCode::Student->value],
            ['name' => RoleCode::Student->name],
        );
        $user = User::factory()->for($school)->create([
            'role_id' => $role->id,
            'name' => $name ?? fake()->name(),
        ]);
        $student = Student::factory()->for($user)->for($school)->create();
        $academicYear = AcademicYear::factory()->for($school)->create();
        $schoolClass = SchoolClass::factory()->for($school)->create();
        $division = Division::factory()->for($schoolClass)->create();
        StudentEnrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'division_id' => $division->id,
            'enrolled_on' => $academicYear->starts_on,
            'status' => 'active',
        ]);

        return [$user, $student, $academicYear];
    }
}
