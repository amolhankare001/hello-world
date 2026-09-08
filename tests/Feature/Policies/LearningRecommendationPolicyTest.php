<?php

namespace Tests\Feature\Policies;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\LearningRecommendation;
use App\Models\Mentor;
use App\Models\Role;
use App\Models\School;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentMentorAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LearningRecommendationPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_only_currently_assigned_mentor_can_manage_recommendation(): void
    {
        $school = School::factory()->create(['timezone' => 'Asia/Kolkata']);
        $mentorRole = Role::query()->create([
            'code' => RoleCode::Mentor->value,
            'name' => 'Mentor',
        ]);
        $mentorUser = User::factory()->for($school)->create(['role_id' => $mentorRole->id]);
        $mentor = Mentor::factory()->for($mentorUser)->for($school)->create();
        $student = Student::factory()->for($school)->create();
        $academicYear = AcademicYear::factory()->for($school)->create();
        $skill = Skill::factory()->create();
        $recommendation = LearningRecommendation::factory()
            ->for($student)
            ->for($academicYear)
            ->for($skill)
            ->create();
        StudentMentorAssignment::query()->create([
            'student_id' => $student->id,
            'mentor_id' => $mentor->id,
            'academic_year_id' => $academicYear->id,
            'assigned_on' => now('Asia/Kolkata')->subDay()->toDateString(),
            'is_primary' => true,
        ]);
        $unassignedUser = User::factory()->for($school)->create(['role_id' => $mentorRole->id]);
        Mentor::factory()->for($unassignedUser)->for($school)->create();

        $this->assertTrue($mentorUser->can('update', $recommendation));
        $this->assertFalse($unassignedUser->can('update', $recommendation));
    }

    public function test_school_administrator_and_cross_school_mentor_are_denied(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $student = Student::factory()->for($school)->create();
        $academicYear = AcademicYear::factory()->for($school)->create();
        $recommendation = LearningRecommendation::factory()
            ->for($student)
            ->for($academicYear)
            ->for(Skill::factory())
            ->create();
        $adminRole = Role::query()->create([
            'code' => RoleCode::SchoolAdmin->value,
            'name' => 'School Administrator',
        ]);
        $admin = User::factory()->for($school)->create(['role_id' => $adminRole->id]);
        $mentorRole = Role::query()->create([
            'code' => RoleCode::Mentor->value,
            'name' => 'Mentor',
        ]);
        $mentorUser = User::factory()->for($otherSchool)->create(['role_id' => $mentorRole->id]);
        Mentor::factory()->for($mentorUser)->for($otherSchool)->create();

        $this->assertFalse($admin->can('update', $recommendation));
        $this->assertFalse($mentorUser->can('update', $recommendation));
    }
}
