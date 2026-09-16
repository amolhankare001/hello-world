<?php

namespace Tests\Feature\Policies;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\Mentor;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentMentorAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_can_view_only_own_profile(): void
    {
        $school = School::factory()->create();
        $user = $this->user(RoleCode::Student, $school);
        $student = Student::factory()->for($user)->for($school)->create();
        $other = Student::factory()->for($this->user(RoleCode::Student, $school))->for($school)->create();

        $this->assertTrue($user->can('view', $student));
        $this->assertFalse($user->can('view', $other));
    }

    public function test_school_admin_can_view_only_students_in_own_school(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $admin = $this->user(RoleCode::SchoolAdmin, $school);
        $student = Student::factory()->for($this->user(RoleCode::Student, $school))->for($school)->create();
        $other = Student::factory()->for($this->user(RoleCode::Student, $otherSchool))->for($otherSchool)->create();

        $this->assertTrue($admin->can('view', $student));
        $this->assertFalse($admin->can('view', $other));
    }

    public function test_mentor_can_view_currently_assigned_student_only(): void
    {
        $school = School::factory()->create(['timezone' => 'Asia/Kolkata']);
        $mentorUser = $this->user(RoleCode::Mentor, $school);
        $mentor = Mentor::factory()->for($mentorUser)->for($school)->create();
        $assigned = Student::factory()->for($this->user(RoleCode::Student, $school))->for($school)->create();
        $unassigned = Student::factory()->for($this->user(RoleCode::Student, $school))->for($school)->create();
        $year = AcademicYear::factory()->for($school)->create();
        StudentMentorAssignment::query()->create([
            'student_id' => $assigned->id,
            'mentor_id' => $mentor->id,
            'academic_year_id' => $year->id,
            'assigned_on' => now('Asia/Kolkata')->subDay()->toDateString(),
            'is_primary' => true,
        ]);

        $this->assertTrue($mentorUser->can('view', $assigned));
        $this->assertFalse($mentorUser->can('view', $unassigned));
    }

    public function test_expired_mentor_assignment_does_not_grant_access(): void
    {
        $school = School::factory()->create(['timezone' => 'Asia/Kolkata']);
        $mentorUser = $this->user(RoleCode::Mentor, $school);
        $mentor = Mentor::factory()->for($mentorUser)->for($school)->create();
        $student = Student::factory()->for($this->user(RoleCode::Student, $school))->for($school)->create();
        $year = AcademicYear::factory()->for($school)->create();
        StudentMentorAssignment::query()->create([
            'student_id' => $student->id,
            'mentor_id' => $mentor->id,
            'academic_year_id' => $year->id,
            'assigned_on' => now('Asia/Kolkata')->subMonth()->toDateString(),
            'ended_on' => now('Asia/Kolkata')->subDay()->toDateString(),
            'is_primary' => true,
        ]);

        $this->assertFalse($mentorUser->can('view', $student));
    }

    public function test_inactive_administrator_is_denied_by_policy(): void
    {
        $school = School::factory()->create();
        $admin = $this->user(RoleCode::SchoolAdmin, $school);
        $student = Student::factory()->for($this->user(RoleCode::Student, $school))->for($school)->create();
        $admin->update(['is_active' => false]);

        $this->assertFalse($admin->can('view', $student));
        $this->assertFalse($admin->can('update', $student));
    }

    private function user(RoleCode $code, School $school): User
    {
        $role = Role::query()->firstOrCreate(
            ['code' => $code->value],
            ['name' => $code->name],
        );

        return User::factory()->for($school)->create(['role_id' => $role->id]);
    }
}
