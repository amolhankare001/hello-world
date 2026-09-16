<?php

namespace Tests\Feature\Policies;

use App\Enums\RoleCode;
use App\Models\Mentor;
use App\Models\Role;
use App\Models\School;
use App\Models\Skill;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_administrator_manages_skill_taxonomy(): void
    {
        $administrator = $this->user(RoleCode::SuperAdmin);
        $skill = Skill::factory()->create();

        $this->assertTrue($administrator->can('viewAny', Skill::class));
        $this->assertTrue($administrator->can('view', $skill));
        $this->assertTrue($administrator->can('create', Skill::class));
        $this->assertTrue($administrator->can('update', $skill));
        $this->assertTrue($administrator->can('delete', $skill));
        $this->assertFalse($administrator->can('restore', $skill));
        $this->assertFalse($administrator->can('forceDelete', $skill));
    }

    public function test_school_users_view_skills_from_accessible_subjects_without_managing_them(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $mentor = $this->user(RoleCode::Mentor, $school);
        $globalSkill = Skill::factory()->for(Subject::factory())->create();
        $ownSkill = Skill::factory()->for(Subject::factory()->for($school))->create();
        $otherSkill = Skill::factory()->for(Subject::factory()->for($otherSchool))->create();

        foreach ([$administrator, $mentor] as $user) {
            $this->assertTrue($user->can('viewAny', Skill::class));
            $this->assertTrue($user->can('view', $globalSkill));
            $this->assertTrue($user->can('view', $ownSkill));
            $this->assertFalse($user->can('view', $otherSkill));
            $this->assertFalse($user->can('create', Skill::class));
            $this->assertFalse($user->can('update', $ownSkill));
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
