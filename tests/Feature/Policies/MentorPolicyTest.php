<?php

namespace Tests\Feature\Policies;

use App\Enums\RoleCode;
use App\Models\Mentor;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentorPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_administrator_manages_only_own_school_mentors(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $ownMentor = $this->mentor($school);
        $otherMentor = $this->mentor($otherSchool);

        $this->assertTrue($administrator->can('viewAny', Mentor::class));
        $this->assertTrue($administrator->can('create', Mentor::class));
        $this->assertTrue($administrator->can('view', $ownMentor));
        $this->assertTrue($administrator->can('update', $ownMentor));
        $this->assertTrue($administrator->can('delete', $ownMentor));
        $this->assertTrue($administrator->can('restore', $ownMentor));
        $this->assertFalse($administrator->can('view', $otherMentor));
        $this->assertFalse($administrator->can('update', $otherMentor));
        $this->assertFalse($administrator->can('delete', $otherMentor));
        $this->assertFalse($administrator->can('restore', $otherMentor));
        $this->assertFalse($administrator->can('forceDelete', $ownMentor));
    }

    public function test_mentor_can_view_only_own_profile(): void
    {
        $school = School::factory()->create();
        $mentor = $this->mentor($school);
        $otherMentor = $this->mentor($school);
        $mentorUser = $mentor->user;

        $this->assertFalse($mentorUser->can('viewAny', Mentor::class));
        $this->assertTrue($mentorUser->can('view', $mentor));
        $this->assertFalse($mentorUser->can('view', $otherMentor));
        $this->assertFalse($mentorUser->can('create', Mentor::class));
        $this->assertFalse($mentorUser->can('update', $mentor));
        $this->assertFalse($mentorUser->can('delete', $mentor));
    }

    public function test_super_administrator_manages_mentor_records(): void
    {
        $school = School::factory()->create();
        $administrator = $this->user(RoleCode::SuperAdmin);
        $mentor = $this->mentor($school);

        $this->assertTrue($administrator->can('viewAny', Mentor::class));
        $this->assertTrue($administrator->can('view', $mentor));
        $this->assertTrue($administrator->can('create', Mentor::class));
        $this->assertTrue($administrator->can('update', $mentor));
        $this->assertTrue($administrator->can('delete', $mentor));
        $this->assertTrue($administrator->can('restore', $mentor));
        $this->assertFalse($administrator->can('forceDelete', $mentor));
    }

    private function mentor(School $school): Mentor
    {
        return Mentor::factory()
            ->for($this->user(RoleCode::Mentor, $school))
            ->for($school)
            ->create();
    }

    private function user(RoleCode $code, ?School $school = null): User
    {
        $role = Role::query()->firstOrCreate(
            ['code' => $code->value],
            ['name' => $code->name],
        );

        $factory = User::factory();

        if ($school !== null) {
            $factory = $factory->for($school);
        }

        return $factory->create(['role_id' => $role->id]);
    }
}
