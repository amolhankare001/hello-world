<?php

namespace Tests\Feature\Game;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\Division;
use App\Models\Game;
use App\Models\Mentor;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentSkillProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentGameTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_student_is_redirected_from_game_catalog(): void
    {
        $this->get(route('games.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_student_is_forbidden_from_game_catalog(): void
    {
        $school = School::factory()->create();
        $role = Role::query()->create([
            'code' => RoleCode::Mentor->value,
            'name' => RoleCode::Mentor->name,
        ]);
        $user = User::factory()->for($school)->create(['role_id' => $role->id]);
        Mentor::factory()->for($user)->for($school)->create();

        $this->actingAs($user)
            ->get(route('games.index'))
            ->assertForbidden();
    }

    public function test_student_lists_only_published_configured_games(): void
    {
        [$user] = $this->studentContext();
        $visibleGame = $this->game('Visible game');
        $draftGame = $this->game('Draft game', 'draft');
        $unconfiguredGame = Game::factory()->create(['title' => 'No level game']);

        $this->actingAs($user)
            ->get(route('games.index'))
            ->assertOk()
            ->assertSee($visibleGame->title)
            ->assertDontSee($draftGame->title)
            ->assertDontSee($unconfiguredGame->title);
    }

    public function test_student_starts_adaptive_game_with_frozen_server_questions(): void
    {
        [$user, $student, $academicYear] = $this->studentContext();
        $game = $this->game('Adaptive game', levelCount: 3);
        StudentSkillProgress::query()->create([
            'student_id' => $student->id,
            'skill_id' => $game->skills->first()->id,
            'academic_year_id' => $academicYear->id,
            'current_level' => 2,
        ]);

        $response = $this->actingAs($user)->post(route('games.start', $game));

        $session = $student->gameSessions()->with('questions')->sole();
        $response->assertRedirect(route('games.sessions.show', $session));
        $this->assertSame(2, $session->difficulty);
        $this->assertSame(2, $session->questions->count());
        $this->assertSame([1, 2], $session->questions->pluck('sequence')->all());
        $this->assertNotNull($session->questions->first()->expected_answer);
    }

    public function test_starting_the_same_game_resumes_the_active_session(): void
    {
        [$user, $student] = $this->studentContext();
        $game = $this->game('Resumable game');

        $firstResponse = $this->actingAs($user)->post(route('games.start', $game));
        $session = $student->gameSessions()->sole();
        $secondResponse = $this->actingAs($user)->post(route('games.start', $game));

        $firstResponse->assertRedirect(route('games.sessions.show', $session));
        $secondResponse->assertRedirect(route('games.sessions.show', $session));
        $this->assertDatabaseCount('game_sessions', 1);
        $this->assertDatabaseCount('game_questions', 2);
    }

    public function test_questions_are_distributed_across_all_mapped_skills(): void
    {
        [$user, $student] = $this->studentContext();
        $game = $this->game('Multi-skill game');
        $secondSkill = Skill::factory()->create();
        $game->skills()->attach($secondSkill->id, ['weight' => 1]);

        $this->actingAs($user)->post(route('games.start', $game));

        $this->assertSame(
            [$game->skills->first()->id, $secondSkill->id],
            $student->gameSessions()->sole()->questions()->orderBy('sequence')->pluck('skill_id')->all(),
        );
    }

    public function test_server_validates_answers_calculates_result_and_updates_learning_record(): void
    {
        [$user, $student, $academicYear] = $this->studentContext();
        $game = $this->game('Validated game');
        $this->actingAs($user)->post(route('games.start', $game));
        $session = $student->gameSessions()->sole();
        $questions = $session->questions()->orderBy('sequence')->get();

        foreach ($questions as $question) {
            $this->actingAs($user)
                ->postJson(route('games.sessions.answer', [$session, $question]), [
                    'answer' => ['value' => data_get($question->expected_answer, 'value')],
                    'score' => 999999,
                    'xp_awarded' => 999999,
                ])
                ->assertOk();
        }

        $session->refresh();
        $result = $session->result()->sole();
        $this->actingAs($user)
            ->get(route('games.sessions.result', $session))
            ->assertOk()
            ->assertSee('100%')
            ->assertSee('एकूण गुण');
        $this->assertSame('completed', $session->status);
        $this->assertSame(2, $result->correct_count);
        $this->assertSame(0, $result->incorrect_count);
        $this->assertSame('100.00', $result->accuracy);
        $this->assertLessThanOrEqual(200, $result->score);
        $this->assertSame(40, $result->xp_awarded);
        $this->assertDatabaseCount('game_answers', 2);
        $this->assertDatabaseCount('student_skill_events', 2);
        $this->assertDatabaseHas('student_skill_events', [
            'student_id' => $student->id,
            'skill_id' => $game->skills->first()->id,
            'academic_year_id' => $academicYear->id,
            'activity_type' => 'game',
            'source_type' => 'game_session',
            'source_id' => $session->id,
        ]);
        $this->assertDatabaseHas('student_skill_progress', [
            'student_id' => $student->id,
            'skill_id' => $game->skills->first()->id,
            'academic_year_id' => $academicYear->id,
            'total_attempts' => 2,
            'correct_attempts' => 2,
            'game_count' => 1,
            'accuracy' => 100,
            'average_score' => 100,
        ]);
        $this->assertDatabaseHas('xp_transactions', [
            'student_id' => $student->id,
            'source_type' => 'game_session',
            'source_id' => $session->id,
            'reason' => 'game_completed',
            'points' => 20,
        ]);
    }

    public function test_wrong_answer_reduces_lives_and_does_not_trust_client_correctness(): void
    {
        [$user, $student] = $this->studentContext();
        $game = $this->game('Lives game');
        $this->actingAs($user)->post(route('games.start', $game));
        $session = $student->gameSessions()->sole();
        $question = $session->questions()->orderBy('sequence')->firstOrFail();
        $wrongValue = collect($question->choices)
            ->pluck('value')
            ->first(fn (mixed $value): bool => (string) $value
                !== (string) data_get($question->expected_answer, 'value'));

        $response = $this->actingAs($user)
            ->postJson(route('games.sessions.answer', [$session, $question]), [
                'answer' => ['value' => $wrongValue],
                'is_correct' => true,
                'score' => 100,
            ])
            ->assertOk();

        $response->assertJsonPath('stats.lives_remaining', 2);
        $response->assertJsonPath('stats.score', 0);
        $this->assertDatabaseHas('game_answers', [
            'game_question_id' => $question->id,
            'is_correct' => false,
            'score' => 0,
        ]);
    }

    public function test_answer_must_match_one_of_the_server_generated_choices(): void
    {
        [$user, $student] = $this->studentContext();
        $game = $this->game('Validated choices game');
        $this->actingAs($user)->post(route('games.start', $game));
        $session = $student->gameSessions()->sole();
        $question = $session->questions()->orderBy('sequence')->firstOrFail();

        $this->actingAs($user)
            ->postJson(route('games.sessions.answer', [$session, $question]), [
                'answer' => ['value' => 'server-did-not-offer-this'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('answer.value');

        $this->assertDatabaseCount('game_answers', 0);
    }

    public function test_answer_after_question_time_limit_is_recorded_as_incorrect(): void
    {
        $this->freezeTime();
        [$user, $student] = $this->studentContext();
        $game = $this->game('Question timer game');
        $this->actingAs($user)->post(route('games.start', $game));
        $session = $student->gameSessions()->sole();
        $question = $session->questions()->orderBy('sequence')->firstOrFail();
        $this->travel(11)->seconds();

        $this->actingAs($user)
            ->postJson(route('games.sessions.answer', [$session, $question]), [
                'answer' => ['value' => data_get($question->expected_answer, 'value')],
            ])
            ->assertOk()
            ->assertJsonPath('stats.score', 0)
            ->assertJsonPath('stats.lives_remaining', 2);

        $this->assertDatabaseHas('game_answers', [
            'game_question_id' => $question->id,
            'is_correct' => false,
            'score' => 0,
        ]);
    }

    public function test_answer_submission_and_completion_are_replay_safe(): void
    {
        [$user, $student] = $this->studentContext();
        $game = $this->game('Replay-safe game');
        $game->levels()->update(['configuration' => [
            'question_count' => 1,
            'choice_count' => 3,
            'item_count' => 3,
            'lives' => 3,
            'response_time_seconds' => 10,
        ]]);
        $this->actingAs($user)->post(route('games.start', $game));
        $session = $student->gameSessions()->sole();
        $question = $session->questions()->sole();
        $payload = ['answer' => ['value' => data_get($question->expected_answer, 'value')]];

        $this->actingAs($user)
            ->postJson(route('games.sessions.answer', [$session, $question]), $payload)
            ->assertOk()
            ->assertJsonPath('completed', true);
        $this->actingAs($user)
            ->postJson(route('games.sessions.answer', [$session, $question]), $payload)
            ->assertOk()
            ->assertJsonPath('is_replay', true);

        $this->assertDatabaseCount('game_answers', 1);
        $this->assertDatabaseCount('game_results', 1);
        $this->assertDatabaseCount('student_skill_events', 1);
        $this->assertDatabaseCount('xp_transactions', 2);
        $this->assertDatabaseHas('student_skill_progress', [
            'student_id' => $student->id,
            'game_count' => 1,
            'total_attempts' => 1,
        ]);
    }

    public function test_student_must_answer_the_current_question_in_order(): void
    {
        [$user, $student] = $this->studentContext();
        $game = $this->game('Ordered game');
        $this->actingAs($user)->post(route('games.start', $game));
        $session = $student->gameSessions()->sole();
        $secondQuestion = $session->questions()->orderBy('sequence')->skip(1)->firstOrFail();

        $this->actingAs($user)
            ->postJson(route('games.sessions.answer', [$session, $secondQuestion]), [
                'answer' => ['value' => data_get($secondQuestion->expected_answer, 'value')],
            ])
            ->assertConflict();

        $this->assertDatabaseCount('game_answers', 0);
    }

    public function test_student_cannot_access_another_students_game_session(): void
    {
        [$firstUser, $firstStudent] = $this->studentContext();
        [$secondUser] = $this->studentContext($firstStudent->school);
        $game = $this->game('Private game');
        $this->actingAs($firstUser)->post(route('games.start', $game));
        $session = $firstStudent->gameSessions()->sole();
        $question = $session->questions()->firstOrFail();

        $this->actingAs($secondUser)
            ->get(route('games.sessions.show', $session))
            ->assertNotFound();
        $this->actingAs($secondUser)
            ->postJson(route('games.sessions.answer', [$session, $question]), [
                'answer' => ['value' => data_get($question->expected_answer, 'value')],
            ])
            ->assertNotFound();
        $this->assertDatabaseCount('game_answers', 0);
    }

    public function test_expired_session_can_be_finished_without_awarding_unearned_xp(): void
    {
        [$user, $student] = $this->studentContext();
        $game = $this->game('Timed game');
        $this->actingAs($user)->post(route('games.start', $game));
        $session = $student->gameSessions()->sole();
        $session->update(['expires_at' => now()->subSecond()]);

        $this->actingAs($user)
            ->postJson(route('games.sessions.finish', $session))
            ->assertOk()
            ->assertJsonPath('completed', true)
            ->assertJsonPath('result.xp_awarded', 0);

        $this->assertDatabaseHas('game_results', [
            'game_session_id' => $session->id,
            'score' => 0,
            'correct_count' => 0,
            'xp_awarded' => 0,
        ]);
        $this->assertDatabaseCount('student_skill_events', 0);
        $this->assertDatabaseCount('xp_transactions', 0);
    }

    public function test_game_page_does_not_serialize_expected_answer_metadata(): void
    {
        [$user, $student] = $this->studentContext();
        $game = $this->game('Hidden metadata game');
        $this->actingAs($user)->post(route('games.start', $game));
        $session = $student->gameSessions()->sole();

        $this->actingAs($user)
            ->get(route('games.sessions.show', $session))
            ->assertOk()
            ->assertDontSee('expected_answer')
            ->assertDontSee('max_score_per_question');
    }

    /**
     * @return array{User, Student, AcademicYear}
     */
    private function studentContext(?School $school = null): array
    {
        $school ??= School::factory()->create();
        $role = Role::query()->firstOrCreate(
            ['code' => RoleCode::Student->value],
            ['name' => RoleCode::Student->name],
        );
        $user = User::factory()->for($school)->create(['role_id' => $role->id]);
        $student = Student::factory()->for($user)->for($school)->create();
        $academicYear = AcademicYear::query()->whereBelongsTo($school)->first()
            ?? AcademicYear::factory()->for($school)->create();
        $schoolClass = SchoolClass::query()->whereBelongsTo($school)->first()
            ?? SchoolClass::factory()->for($school)->create();
        $division = Division::query()->whereBelongsTo($schoolClass)->first()
            ?? Division::factory()->for($schoolClass)->create();
        StudentEnrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'division_id' => $division->id,
            'enrolled_on' => $academicYear->starts_on,
            'status' => 'active',
        ]);

        return [$user, $student, $academicYear];
    }

    private function game(
        string $title,
        string $status = 'published',
        int $levelCount = 1,
    ): Game {
        $skill = Skill::factory()->create();
        $game = Game::factory()->create([
            'title' => $title,
            'title_marathi' => $title,
            'status' => $status,
        ]);
        $game->skills()->attach($skill->id, ['weight' => 1]);

        foreach (range(1, $levelCount) as $level) {
            $game->levels()->create([
                'level' => $level,
                'name' => "Level {$level}",
                'name_marathi' => "पातळी {$level}",
                'difficulty' => $level,
                'configuration' => [
                    'question_count' => 2,
                    'choice_count' => 3,
                    'item_count' => 3,
                    'lives' => 3,
                    'response_time_seconds' => 10,
                    'minimum_difficulty' => 1,
                    'maximum_difficulty' => $levelCount,
                ],
                'target_score' => 140,
                'time_limit_seconds' => 60,
            ]);
        }

        return $game->load('levels', 'skills');
    }
}
