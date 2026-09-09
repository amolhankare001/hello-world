<?php

namespace Tests\Feature\Policies;

use App\Enums\RoleCode;
use App\Models\Activity;
use App\Models\Mentor;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_administrator_manages_global_and_school_activities(): void
    {
        $administrator = $this->user(RoleCode::SuperAdmin);
        $globalActivity = Activity::factory()->create();
        $schoolActivity = Activity::factory()->for(School::factory())->create();

        $this->assertTrue($administrator->can('viewAny', Activity::class));
        $this->assertTrue($administrator->can('create', Activity::class));

        foreach ([$globalActivity, $schoolActivity] as $activity) {
            $this->assertTrue($administrator->can('view', $activity));
            $this->assertTrue($administrator->can('update', $activity));
            $this->assertTrue($administrator->can('delete', $activity));
            $this->assertTrue($administrator->can('restore', $activity));
            $this->assertFalse($administrator->can('forceDelete', $activity));
        }
    }

    public function test_school_users_manage_only_own_school_activities_and_view_global_content(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $mentor = $this->user(RoleCode::Mentor, $school);
        $globalActivity = Activity::factory()->create();
        $ownActivity = Activity::factory()->for($school)->create();
        $otherActivity = Activity::factory()->for($otherSchool)->create();

        foreach ([$administrator, $mentor] as $user) {
            $this->assertTrue($user->can('viewAny', Activity::class));
            $this->assertTrue($user->can('create', Activity::class));
            $this->assertTrue($user->can('view', $globalActivity));
            $this->assertFalse($user->can('update', $globalActivity));
            $this->assertTrue($user->can('view', $ownActivity));
            $this->assertTrue($user->can('update', $ownActivity));
            $this->assertTrue($user->can('delete', $ownActivity));
            $this->assertFalse($user->can('view', $otherActivity));
            $this->assertFalse($user->can('update', $otherActivity));
            $this->assertFalse($user->can('forceDelete', $ownActivity));
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
