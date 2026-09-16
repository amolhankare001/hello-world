<?php

namespace Tests\Feature\Policies;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\Intervention;
use App\Models\Mentor;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentMentorAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class InterventionPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_only_owning_currently_assigned_mentor_can_update_intervention(): void
    {
        $school = School::factory()->create(['timezone' => 'Asia/Kolkata']);
        $role = Role::query()->create([
            'code' => RoleCode::Mentor->value,
            'name' => 'Mentor',
        ]);
        $ownerUser = User::factory()->for($school)->create(['role_id' => $role->id]);
        $owner = Mentor::factory()->for($ownerUser)->for($school)->create();
        $otherUser = User::factory()->for($school)->create(['role_id' => $role->id]);
        Mentor::factory()->for($otherUser)->for($school)->create();
        $student = Student::factory()->for($school)->create();
        $academicYear = AcademicYear::factory()->for($school)->create();
        StudentMentorAssignment::query()->create([
            'student_id' => $student->id,
            'mentor_id' => $owner->id,
            'academic_year_id' => $academicYear->id,
            'assigned_on' => now('Asia/Kolkata')->subDay()->toDateString(),
            'is_primary' => true,
        ]);
        $intervention = Intervention::factory()
            ->for($student)
            ->for($owner)
            ->for($academicYear)
            ->create();

        $this->assertTrue($ownerUser->can('update', $intervention));
        $this->assertFalse($otherUser->can('update', $intervention));
    }

    public function test_expired_assignment_revokes_intervention_access(): void
    {
        $school = School::factory()->create(['timezone' => 'Asia/Kolkata']);
        $role = Role::query()->create([
            'code' => RoleCode::Mentor->value,
            'name' => 'Mentor',
        ]);
        $user = User::factory()->for($school)->create(['role_id' => $role->id]);
        $mentor = Mentor::factory()->for($user)->for($school)->create();
        $student = Student::factory()->for($school)->create();
        $academicYear = AcademicYear::factory()->for($school)->create();
        StudentMentorAssignment::query()->create([
            'student_id' => $student->id,
            'mentor_id' => $mentor->id,
            'academic_year_id' => $academicYear->id,
            'assigned_on' => now('Asia/Kolkata')->subMonth()->toDateString(),
            'ended_on' => now('Asia/Kolkata')->subDay()->toDateString(),
            'is_primary' => true,
        ]);
        $intervention = Intervention::factory()
            ->for($student)
            ->for($mentor)
            ->for($academicYear)
            ->create();

        $this->assertFalse($user->can('update', $intervention));
    }
}
