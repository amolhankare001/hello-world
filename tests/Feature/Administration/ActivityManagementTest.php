<?php

namespace Tests\Feature\Administration;

use App\Enums\RoleCode;
use App\Models\Activity;
use App\Models\Mentor;
use App\Models\Role;
use App\Models\School;
use App\Models\Skill;
use App\Models\SkillLevel;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ActivityManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_school_administrator_lists_global_and_own_activities_only(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $globalActivity = Activity::factory()->create(['title_marathi' => 'सर्वांसाठी']);
        $ownActivity = Activity::factory()->for($school)->create(['title_marathi' => 'आमच्या शाळेसाठी']);
        $otherActivity = Activity::factory()->for($otherSchool)->create(['title_marathi' => 'दुसऱ्या शाळेसाठी']);

        $this->actingAs($administrator)
            ->get(route('activities.index'))
            ->assertOk()
            ->assertSee($globalActivity->title_marathi)
            ->assertSee($ownActivity->title_marathi)
            ->assertDontSee($otherActivity->title_marathi);
    }

    public function test_school_administrator_creates_an_activity_for_own_school_and_records_audit(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $skill = Skill::factory()->create();
        $level = SkillLevel::factory()->for($skill)->create();

        $this->actingAs($administrator)
            ->post(route('activities.store'), $this->activityPayload($skill, [
                'school_id' => $otherSchool->id,
                'skill_level_id' => $level->id,
                'code' => 'school-read',
                'status' => 'published',
            ]))
            ->assertRedirect();

        $activity = Activity::query()->sole();
        $this->assertSame($school->id, $activity->school_id);
        $this->assertSame('SCHOOL-READ', $activity->code);
        $this->assertSame(['पहिले उदाहरण', 'दुसरे उदाहरण'], $activity->content['examples']);
        $this->assertNotNull($activity->published_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'activity.created']);
    }

    public function test_mentor_can_create_an_activity_for_own_school(): void
    {
        $school = School::factory()->create();
        $mentor = $this->user(RoleCode::Mentor, $school);
        $skill = Skill::factory()->create();

        $this->actingAs($mentor)
            ->post(route('activities.store'), $this->activityPayload($skill, ['code' => 'mentor-task']))
            ->assertRedirect();

        $this->assertDatabaseHas('activities', [
            'school_id' => $school->id,
            'created_by' => $mentor->id,
            'code' => 'MENTOR-TASK',
        ]);
    }

    public function test_super_administrator_creates_a_global_activity(): void
    {
        $administrator = $this->user(RoleCode::SuperAdmin);
        $skill = Skill::factory()->create();

        $this->actingAs($administrator)
            ->post(route('activities.store'), $this->activityPayload($skill, [
                'school_id' => null,
                'code' => 'global-task',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('activities', ['school_id' => null, 'code' => 'GLOBAL-TASK']);
    }

    public function test_school_administrator_updates_own_activity_but_not_global_activity(): void
    {
        $school = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $skill = Skill::factory()->create();
        $ownActivity = Activity::factory()->for($school)->for($skill)->create(['status' => 'draft']);
        $globalActivity = Activity::factory()->for($skill)->create();

        $this->actingAs($administrator)
            ->put(route('activities.update', $ownActivity), $this->activityPayload($skill, [
                'code' => $ownActivity->code,
                'title' => 'Updated title',
                'status' => 'published',
            ]))
            ->assertRedirect(route('activities.edit', $ownActivity));

        $this->assertDatabaseHas('activities', ['id' => $ownActivity->id, 'title' => 'Updated title']);
        $this->assertNotNull($ownActivity->fresh()->published_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'activity.updated']);

        $this->actingAs($administrator)
            ->put(route('activities.update', $globalActivity), $this->activityPayload($skill, [
                'code' => $globalActivity->code,
            ]))
            ->assertForbidden();
    }

    public function test_cross_school_activity_route_returns_not_found(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $activity = Activity::factory()->for($otherSchool)->create();

        $this->actingAs($administrator)
            ->get(route('activities.edit', $activity))
            ->assertNotFound();
    }

    public function test_activity_requires_a_level_from_the_selected_skill(): void
    {
        $school = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $skill = Skill::factory()->create();
        $otherLevel = SkillLevel::factory()->create();

        $this->actingAs($administrator)
            ->post(route('activities.store'), $this->activityPayload($skill, [
                'skill_level_id' => $otherLevel->id,
            ]))
            ->assertSessionHasErrors('skill_level_id');

        $this->assertDatabaseCount('activities', 0);
    }

    public function test_school_user_cannot_use_a_skill_from_another_school_subject(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $administrator = $this->user(RoleCode::SchoolAdmin, $school);
        $skill = Skill::factory()->for(Subject::factory()->for($otherSchool))->create();

        $this->actingAs($administrator)
            ->post(route('activities.store'), $this->activityPayload($skill))
            ->assertSessionHasErrors('skill_id');

        $this->assertDatabaseCount('activities', 0);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function activityPayload(Skill $skill, array $overrides = []): array
    {
        return [
            'school_id' => null,
            'skill_id' => $skill->id,
            'skill_level_id' => null,
            'code' => 'READ-01',
            'type' => 'learn',
            'title' => 'Read a short passage',
            'title_marathi' => 'लहान उतारा वाचा',
            'instructions' => 'Read and discuss.',
            'instructions_marathi' => 'वाचा आणि चर्चा करा.',
            'content_body' => 'A short learning passage.',
            'content_body_marathi' => 'एक लहान अध्ययन उतारा.',
            'examples' => "पहिले उदाहरण\nदुसरे उदाहरण",
            'difficulty' => 1,
            'estimated_minutes' => 10,
            'max_score' => 10,
            'status' => 'draft',
            ...$overrides,
        ];
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
