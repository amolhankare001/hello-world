<?php

namespace Tests\Feature\Policies;

use App\Enums\RoleCode;
use App\Models\Mentor;
use App\Models\Role;
use App\Models\School;
use App\Models\Skill;
use App\Models\SkillLevel;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillLevelPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_administrator_manages_skill_levels(): void
    {
        $administrator = $this->user(RoleCode::SuperAdmin);
        $level = SkillLevel::factory()->create();

        $this->assertTrue($administrator->can('viewAny', SkillLevel::class));
        $this->assertTrue($administrator->can('view', $level));
        $this->assertTrue($administrator->can('create', SkillLevel::class));
        $this->assertTrue($administrator->can('update', $level));
        $this->assertTrue($administrator->can('delete', $level));
        $this->assertFalse($administrator->can('restore', $level));
        $this->assertFalse($administrator->can('forceDelete', $level));
    }

    public function test_school_users_view_levels_from_accessible_subjects_without_managing_them(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $mentor = $this->user(RoleCode::Mentor, $school);
        $globalLevel = SkillLevel::factory()->for(Skill::factory()->for(Subject::factory()))->create();
        $ownLevel = SkillLevel::factory()->for(Skill::factory()->for(Subject::factory()->for($school)))->create();
        $otherLevel = SkillLevel::factory()->for(Skill::factory()->for(Subject::factory()->for($otherSchool)))->create();

        foreach ([$administrator, $mentor] as $user) {
            $this->assertTrue($user->can('viewAny', SkillLevel::class));
            $this->assertTrue($user->can('view', $globalLevel));
            $this->assertTrue($user->can('view', $ownLevel));
            $this->assertFalse($user->can('view', $otherLevel));
            $this->assertFalse($user->can('create', SkillLevel::class));
            $this->assertFalse($user->can('update', $ownLevel));
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
