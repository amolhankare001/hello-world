<?php

namespace Tests\Feature\Administration;

use App\Enums\RoleCode;
use App\Models\Role;
use App\Models\School;
use App\Models\Skill;
use App\Models\SkillLevel;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CurriculumManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_super_administrator_creates_and_updates_a_subject_with_audit_events(): void
    {
        $administrator = $this->user(RoleCode::SuperAdmin);

        $this->actingAs($administrator)
            ->post(route('subjects.store'), $this->subjectPayload(['code' => 'math']))
            ->assertRedirect();

        $subject = Subject::query()->sole();
        $this->assertSame('MATH', $subject->code);
        $this->assertDatabaseHas('audit_logs', ['action' => 'subject.created']);

        $this->actingAs($administrator)
            ->put(route('subjects.update', $subject), $this->subjectPayload([
                'code' => 'MATHS',
                'name' => 'Updated Mathematics',
            ]))
            ->assertRedirect(route('subjects.edit', $subject));

        $this->assertDatabaseHas('subjects', ['id' => $subject->id, 'code' => 'MATHS']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'subject.updated']);
    }

    public function test_duplicate_subject_code_returns_validation_error(): void
    {
        $administrator = $this->user(RoleCode::SuperAdmin);
        Subject::factory()->create(['code' => 'MATH']);

        $this->actingAs($administrator)
            ->post(route('subjects.store'), $this->subjectPayload(['code' => 'math']))
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('subjects', 1);
    }

    public function test_school_administrator_cannot_manage_curriculum_taxonomy(): void
    {
        $school = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);

        $this->actingAs($administrator)
            ->get(route('subjects.index'))
            ->assertForbidden();
    }

    public function test_super_administrator_creates_and_updates_a_hierarchical_skill(): void
    {
        $administrator = $this->user(RoleCode::SuperAdmin);
        $subject = Subject::factory()->create();
        $parent = Skill::factory()->for($subject)->create();

        $this->actingAs($administrator)
            ->post(route('subjects.skills.store', $subject), $this->skillPayload([
                'parent_skill_id' => $parent->id,
                'code' => 'child',
            ]))
            ->assertRedirect(route('subjects.edit', $subject));

        $skill = Skill::query()->where('code', 'CHILD')->sole();
        $this->assertSame($parent->id, $skill->parent_skill_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'skill.created']);

        $this->actingAs($administrator)
            ->put(route('subjects.skills.update', [$subject, $skill]), $this->skillPayload([
                'parent_skill_id' => null,
                'code' => 'updated-child',
            ]))
            ->assertRedirect(route('subjects.skills.edit', [$subject, $skill]));

        $this->assertDatabaseHas('skills', [
            'id' => $skill->id,
            'code' => 'UPDATED-CHILD',
            'parent_skill_id' => null,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'skill.updated']);
    }

    public function test_skill_cannot_be_moved_below_its_descendant(): void
    {
        $administrator = $this->user(RoleCode::SuperAdmin);
        $subject = Subject::factory()->create();
        $parent = Skill::factory()->for($subject)->create();
        $child = Skill::factory()->for($subject)->create(['parent_skill_id' => $parent->id]);

        $this->actingAs($administrator)
            ->put(route('subjects.skills.update', [$subject, $parent]), $this->skillPayload([
                'code' => $parent->code,
                'parent_skill_id' => $child->id,
            ]))
            ->assertSessionHasErrors('parent_skill_id');

        $this->assertNull($parent->fresh()->parent_skill_id);
    }

    public function test_super_administrator_creates_and_updates_a_skill_level(): void
    {
        $administrator = $this->user(RoleCode::SuperAdmin);
        $subject = Subject::factory()->create();
        $skill = Skill::factory()->for($subject)->create();

        $this->actingAs($administrator)
            ->post(route('subjects.skills.levels.store', [$subject, $skill]), $this->levelPayload())
            ->assertRedirect(route('subjects.skills.edit', [$subject, $skill]));

        $level = SkillLevel::query()->sole();
        $this->assertDatabaseHas('audit_logs', ['action' => 'skill_level.created']);

        $this->actingAs($administrator)
            ->put(route('subjects.skills.levels.update', [$subject, $skill, $level]), $this->levelPayload([
                'name' => 'Advanced Foundation',
                'mastery_threshold' => 85,
            ]))
            ->assertRedirect(route('subjects.skills.edit', [$subject, $skill]));

        $this->assertDatabaseHas('skill_levels', [
            'id' => $level->id,
            'name' => 'Advanced Foundation',
            'mastery_threshold' => 85,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'skill_level.updated']);
    }

    public function test_nested_skill_and_level_routes_conceal_mismatched_parents(): void
    {
        $administrator = $this->user(RoleCode::SuperAdmin);
        $subject = Subject::factory()->create();
        $otherSubject = Subject::factory()->create();
        $skill = Skill::factory()->for($otherSubject)->create();
        $ownSkill = Skill::factory()->for($subject)->create();
        $otherSkill = Skill::factory()->for($subject)->create();
        $level = SkillLevel::factory()->for($otherSkill)->create();

        $this->actingAs($administrator)
            ->get(route('subjects.skills.edit', [$subject, $skill]))
            ->assertNotFound();

        $this->actingAs($administrator)
            ->put(
                route('subjects.skills.levels.update', [$subject, $ownSkill, $level]),
                $this->levelPayload(),
            )
            ->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function subjectPayload(array $overrides = []): array
    {
        return [
            'code' => 'MAR',
            'name' => 'Marathi',
            'name_marathi' => 'मराठी',
            'description' => 'Language curriculum',
            'sort_order' => 1,
            'is_active' => '1',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function skillPayload(array $overrides = []): array
    {
        return [
            'parent_skill_id' => null,
            'code' => 'LETTERS',
            'name' => 'Letter recognition',
            'name_marathi' => 'अक्षर ओळख',
            'description' => 'Recognise Marathi letters.',
            'sort_order' => 1,
            'is_active' => '1',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function levelPayload(array $overrides = []): array
    {
        return [
            'level' => 1,
            'name' => 'Foundation',
            'name_marathi' => 'पायाभूत',
            'learning_objective' => 'Recognise the concept.',
            'mastery_threshold' => 80,
            ...$overrides,
        ];
    }

    private function user(RoleCode $code, ?School $school = null): User
    {
        $role = Role::query()->firstOrCreate(['code' => $code->value], ['name' => $code->name]);

        return User::factory()->create(['role_id' => $role->id, 'school_id' => $school?->id]);
    }
}
