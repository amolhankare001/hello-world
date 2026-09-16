<?php

namespace Tests\Feature\Administration;

use App\Enums\RoleCode;
use App\Models\Activity;
use App\Models\ErrorType;
use App\Models\Mentor;
use App\Models\PracticeActivity;
use App\Models\Role;
use App\Models\School;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QuestionManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_mentor_creates_a_bilingual_mcq_with_error_classification(): void
    {
        $school = School::factory()->create();
        $mentor = $this->user(RoleCode::Mentor, $school);
        $skill = Skill::factory()->create();
        $activity = Activity::factory()->for($school)->for($skill)->create(['type' => 'practice']);
        PracticeActivity::factory()->for($activity)->create();
        $errorType = ErrorType::factory()->for($skill)->create();

        $this->actingAs($mentor)
            ->post(route('activities.questions.store', $activity), [
                'error_type_id' => $errorType->id,
                'type' => 'mcq',
                'prompt' => 'Which is a vowel?',
                'prompt_marathi' => 'स्वर कोणता?',
                'correct_answer' => null,
                'options' => "*A | अ\nK | क\nM | म",
                'explanation' => 'A is the vowel.',
                'explanation_marathi' => 'अ हा स्वर आहे.',
                'difficulty' => 1,
                'marks' => 2,
                'is_active' => 1,
            ])
            ->assertRedirect(route('activities.edit', $activity));

        $question = $activity->questions()->with('options')->sole();
        $this->assertSame($skill->id, $question->skill_id);
        $this->assertSame($mentor->id, $question->created_by);
        $this->assertCount(3, $question->options);
        $this->assertSame('A', $question->options->firstWhere('is_correct', true)->label);
        $this->assertDatabaseHas('audit_logs', ['action' => 'question.created']);
    }

    public function test_question_requires_a_marked_correct_selection_option(): void
    {
        $administrator = $this->user(RoleCode::SuperAdmin);
        $activity = Activity::factory()->for(Skill::factory())->create(['type' => 'practice']);
        PracticeActivity::factory()->for($activity)->create();

        $this->actingAs($administrator)
            ->post(route('activities.questions.store', $activity), [
                'type' => 'mcq',
                'prompt' => 'Choose one.',
                'prompt_marathi' => 'एक निवडा.',
                'options' => "A | अ\nB | ब",
                'difficulty' => 1,
                'marks' => 1,
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('options');

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_mcq_rejects_multiple_correct_options(): void
    {
        $administrator = $this->user(RoleCode::SuperAdmin);
        $activity = Activity::factory()->for(Skill::factory())->create(['type' => 'practice']);
        PracticeActivity::factory()->for($activity)->create();

        $this->actingAs($administrator)
            ->post(route('activities.questions.store', $activity), [
                'type' => 'mcq',
                'prompt' => 'Choose one.',
                'prompt_marathi' => 'एक निवडा.',
                'options' => "*A | अ\n*B | ब",
                'difficulty' => 1,
                'marks' => 1,
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('options');

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_number_question_update_replaces_options_and_answer_key(): void
    {
        $administrator = $this->user(RoleCode::SuperAdmin);
        $skill = Skill::factory()->create();
        $activity = Activity::factory()->for($skill)->create(['type' => 'practice']);
        PracticeActivity::factory()->for($activity)->create();
        $question = $activity->questions()->create([
            'skill_id' => $skill->id,
            'type' => 'mcq',
            'prompt' => 'Old',
            'prompt_marathi' => 'जुना',
            'correct_answer' => [],
            'difficulty' => 1,
            'marks' => 1,
            'is_active' => true,
        ]);
        $question->options()->createMany([
            ['label' => 'A', 'is_correct' => true, 'sort_order' => 1],
            ['label' => 'B', 'is_correct' => false, 'sort_order' => 2],
        ]);

        $this->actingAs($administrator)
            ->put(route('activities.questions.update', [$activity, $question]), [
                'type' => 'number_input',
                'prompt' => 'Two plus three?',
                'prompt_marathi' => 'दोन अधिक तीन?',
                'correct_answer' => '5',
                'options' => null,
                'difficulty' => 2,
                'marks' => 3,
                'is_active' => 1,
            ])
            ->assertRedirect(route('activities.questions.edit', [$activity, $question]));

        $question->refresh();
        $this->assertSame(['value' => '5'], $question->correct_answer);
        $this->assertSame(2, $question->difficulty);
        $this->assertDatabaseCount('question_options', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'question.updated']);
    }

    public function test_practice_thresholds_are_configurable(): void
    {
        $school = School::factory()->create();
        $mentor = $this->user(RoleCode::Mentor, $school);
        $activity = Activity::factory()->for($school)->create(['type' => 'practice']);
        $practice = PracticeActivity::factory()->for($activity)->create();

        $this->actingAs($mentor)
            ->put(route('activities.practice.update', $activity), [
                'question_count' => 7,
                'randomize_questions' => 0,
                'show_feedback_immediately' => 1,
                'difficulty_up_accuracy' => 85,
                'remedial_accuracy' => 55,
                'minimum_difficulty' => 1,
                'maximum_difficulty' => 4,
            ])
            ->assertRedirect(route('activities.edit', $activity));

        $practice->refresh();
        $this->assertSame(7, $practice->question_count);
        $this->assertFalse($practice->randomize_questions);
        $this->assertSame(85, $practice->configuration['difficulty_up_accuracy']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'practice_activity.updated']);
    }

    public function test_cross_school_question_route_is_not_found(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $mentor = $this->user(RoleCode::Mentor, $school);
        $activity = Activity::factory()->for($otherSchool)->for(Skill::factory())->create(['type' => 'practice']);
        $question = $activity->questions()->create([
            'skill_id' => $activity->skill_id,
            'type' => 'text_input',
            'prompt' => 'Question',
            'prompt_marathi' => 'प्रश्न',
            'correct_answer' => ['accepted' => ['answer']],
            'difficulty' => 1,
            'marks' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($mentor)
            ->get(route('activities.questions.edit', [$activity, $question]))
            ->assertNotFound();
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
