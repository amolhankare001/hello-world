<?php

namespace Tests\Feature\Policies;

use App\Enums\RoleCode;
use App\Models\Mentor;
use App\Models\Role;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_administrator_manages_subject_taxonomy(): void
    {
        $administrator = $this->user(RoleCode::SuperAdmin);
        $subject = Subject::factory()->create();

        $this->assertTrue($administrator->can('viewAny', Subject::class));
        $this->assertTrue($administrator->can('view', $subject));
        $this->assertTrue($administrator->can('create', Subject::class));
        $this->assertTrue($administrator->can('update', $subject));
        $this->assertTrue($administrator->can('delete', $subject));
        $this->assertFalse($administrator->can('restore', $subject));
        $this->assertFalse($administrator->can('forceDelete', $subject));
    }

    public function test_school_users_view_only_global_and_own_school_subjects(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $mentor = $this->user(RoleCode::Mentor, $school);
        $globalSubject = Subject::factory()->create();
        $ownSubject = Subject::factory()->for($school)->create();
        $otherSubject = Subject::factory()->for($otherSchool)->create();

        foreach ([$administrator, $mentor] as $user) {
            $this->assertTrue($user->can('viewAny', Subject::class));
            $this->assertTrue($user->can('view', $globalSubject));
            $this->assertTrue($user->can('view', $ownSubject));
            $this->assertFalse($user->can('view', $otherSubject));
            $this->assertFalse($user->can('create', Subject::class));
            $this->assertFalse($user->can('update', $ownSubject));
        }
    }

    private function user(RoleCode $code, ?School $school = null): User
    {
        $role = Role::query()->firstOrCreate(['code' => $code->value], ['name' => $code->name]);
        $user = User::factory()->create(['role_id' => $role->id, 'school_id' => $school?->id]);

        if ($code === RoleCode::Mentor) {
            Mentor::factory()->for($user)->for($school)->create();
        }

        return $user;
    }
}
