<?php

namespace Tests\Feature\Simulation;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\Division;
use App\Models\Mentor;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Simulation;
use App\Models\SimulationEvent;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentSkillProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentSimulationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_student_is_redirected_from_simulation_catalog(): void
    {
        $this->get(route('simulations.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_student_is_forbidden_from_simulation_catalog(): void
    {
        $school = School::factory()->create();
        $role = Role::query()->create([
            'code' => RoleCode::Mentor->value,
            'name' => RoleCode::Mentor->name,
        ]);
        $user = User::factory()->for($school)->create(['role_id' => $role->id]);
        Mentor::factory()->for($user)->for($school)->create();

        $this->actingAs($user)
            ->get(route('simulations.index'))
            ->assertForbidden();
    }

    public function test_student_lists_only_published_skill_mapped_simulations(): void
    {
        [$user] = $this->studentContext();
        $visible = $this->simulation('Visible simulation');
        $draft = $this->simulation('Draft simulation', 'draft');
        $unconfigured = Simulation::factory()->create(['title' => 'No skill simulation']);

        $this->actingAs($user)
            ->get(route('simulations.index'))
            ->assertOk()
            ->assertSee($visible->title)
            ->assertDontSee($draft->title)
            ->assertDontSee($unconfigured->title);
    }

    public function test_student_starts_adaptive_simulation_with_frozen_server_challenges(): void
    {
        [$user, $student, $academicYear] = $this->studentContext();
        $simulation = $this->simulation('Adaptive simulation');
        StudentSkillProgress::query()->create([
            'student_id' => $student->id,
            'skill_id' => $simulation->skills->first()->id,
            'academic_year_id' => $academicYear->id,
            'current_level' => 2,
        ]);

        $response = $this->actingAs($user)->post(route('simulations.start', $simulation));

        $session = $student->simulationSessions()->with('challenges')->sole();
        $response->assertRedirect(route('simulations.sessions.show', $session));
        $this->assertSame(2, $session->difficulty);
        $this->assertSame(2, $session->challenges->count());
        $this->assertSame([1, 2], $session->challenges->pluck('sequence')->all());
        $this->assertNotNull($session->challenges->first()->expected_state);
        $this->assertArrayNotHasKey('expected_state', $session->challenges->first()->toArray());
    }

    public function test_starting_the_same_simulation_resumes_the_active_session(): void
    {
        [$user, $student] = $this->studentContext();
        $simulation = $this->simulation('Resumable simulation');

        $firstResponse = $this->actingAs($user)->post(route('simulations.start', $simulation));
        $session = $student->simulationSessions()->sole();
        $secondResponse = $this->actingAs($user)->post(route('simulations.start', $simulation));

        $firstResponse->assertRedirect(route('simulations.sessions.show', $session));
        $secondResponse->assertRedirect(route('simulations.sessions.show', $session));
        $this->assertDatabaseCount('simulation_sessions', 1);
        $this->assertDatabaseCount('simulation_challenges', 2);
    }

    public function test_server_validates_events_calculates_result_and_updates_learning_record(): void
    {
        [$user, $student, $academicYear] = $this->studentContext();
        $simulation = $this->simulation('Validated simulation');
        $this->actingAs($user)->post(route('simulations.start', $simulation));
        $session = $student->simulationSessions()->sole();
        $challenges = $session->challenges()->orderBy('sequence')->get();

        foreach ($challenges as $challenge) {
            $this->actingAs($user)
                ->postJson(route('simulations.sessions.submit', [$session, $challenge]), [
                    'state' => $challenge->expected_state,
                    'score' => 999999,
                    'xp_awarded' => 999999,
                ])
                ->assertOk();
        }

        $session->refresh();
        $result = $session->result()->sole();
        $this->assertSame('completed', $session->status);
        $this->assertSame(2, $result->successful_count);
        $this->assertSame(0, $result->unsuccessful_count);
        $this->assertSame('100.00', $result->accuracy);
        $this->assertLessThanOrEqual(200, $result->score);
        $this->assertSame(40, $result->xp_awarded);
        $this->assertDatabaseCount('simulation_events', 2);
        $this->assertDatabaseCount('student_skill_events', 2);
        $this->assertDatabaseHas('student_skill_events', [
            'student_id' => $student->id,
            'skill_id' => $simulation->skills->first()->id,
            'academic_year_id' => $academicYear->id,
            'activity_type' => 'simulation',
            'source_type' => 'simulation_session',
            'source_id' => $session->id,
        ]);
        $this->assertDatabaseHas('student_skill_progress', [
            'student_id' => $student->id,
            'skill_id' => $simulation->skills->first()->id,
            'academic_year_id' => $academicYear->id,
            'total_attempts' => 2,
            'correct_attempts' => 2,
            'simulation_count' => 1,
            'accuracy' => 100,
            'average_score' => 100,
        ]);
        $this->assertDatabaseHas('xp_transactions', [
            'student_id' => $student->id,
            'source_type' => 'simulation_session',
            'source_id' => $session->id,
            'reason' => 'simulation_completed',
            'points' => 20,
        ]);
    }

    public function test_three_unsuccessful_attempts_advance_without_trusting_client_result_fields(): void
    {
        [$user, $student] = $this->studentContext();
        $simulation = $this->simulation('Attempt simulation');
        $this->actingAs($user)->post(route('simulations.start', $simulation));
        $session = $student->simulationSessions()->sole();
        $challenge = $session->challenges()->orderBy('sequence')->firstOrFail();
        $wrongValue = (int) data_get($challenge->expected_state, 'value') + 1;

        foreach (range(1, 3) as $attempt) {
            $response = $this->actingAs($user)
                ->postJson(route('simulations.sessions.submit', [$session, $challenge]), [
                    'state' => ['value' => $wrongValue],
                    'is_success' => true,
                    'score' => 999999,
                ])
                ->assertOk();
        }

        $response
            ->assertJsonPath('stats.score', 0)
            ->assertJsonPath('stats.unsuccessful_count', 1)
            ->assertJsonPath('stats.completed_count', 1);
        $this->assertDatabaseCount('simulation_events', 3);
        $this->assertDatabaseMissing('simulation_events', [
            'simulation_challenge_id' => $challenge->id,
            'is_success' => true,
        ]);
    }

    public function test_student_must_complete_simulation_challenges_in_order(): void
    {
        [$user, $student] = $this->studentContext();
        $simulation = $this->simulation('Ordered simulation');
        $this->actingAs($user)->post(route('simulations.start', $simulation));
        $session = $student->simulationSessions()->sole();
        $secondChallenge = $session->challenges()->orderBy('sequence')->skip(1)->firstOrFail();

        $this->actingAs($user)
            ->postJson(route('simulations.sessions.submit', [$session, $secondChallenge]), [
                'state' => $secondChallenge->expected_state,
            ])
            ->assertConflict();

        $this->assertDatabaseCount('simulation_events', 0);
    }

    public function test_completion_submission_is_replay_safe(): void
    {
        [$user, $student] = $this->studentContext();
        $simulation = $this->simulation('Replay simulation', challengeCount: 1);
        $this->actingAs($user)->post(route('simulations.start', $simulation));
        $session = $student->simulationSessions()->sole();
        $challenge = $session->challenges()->sole();
        $payload = ['state' => $challenge->expected_state];

        $this->actingAs($user)
            ->postJson(route('simulations.sessions.submit', [$session, $challenge]), $payload)
            ->assertOk()
            ->assertJsonPath('completed', true);
        $this->actingAs($user)
            ->postJson(route('simulations.sessions.submit', [$session, $challenge]), $payload)
            ->assertOk()
            ->assertJsonPath('is_replay', true);

        $this->assertDatabaseCount('simulation_events', 1);
        $this->assertDatabaseCount('simulation_results', 1);
        $this->assertDatabaseCount('student_skill_events', 1);
        $this->assertDatabaseHas('student_skill_progress', [
            'student_id' => $student->id,
            'simulation_count' => 1,
            'total_attempts' => 1,
        ]);
    }

    public function test_student_cannot_access_another_students_simulation_session(): void
    {
        [$firstUser, $firstStudent] = $this->studentContext();
        [$secondUser] = $this->studentContext($firstStudent->school);
        $simulation = $this->simulation('Private simulation');
        $this->actingAs($firstUser)->post(route('simulations.start', $simulation));
        $session = $firstStudent->simulationSessions()->sole();
        $challenge = $session->challenges()->firstOrFail();

        $this->actingAs($secondUser)
            ->get(route('simulations.sessions.show', $session))
            ->assertNotFound();
        $this->actingAs($secondUser)
            ->postJson(route('simulations.sessions.submit', [$session, $challenge]), [
                'state' => $challenge->expected_state,
            ])
            ->assertNotFound();
        $this->assertDatabaseCount('simulation_events', 0);
    }

    public function test_simulation_page_does_not_serialize_expected_state(): void
    {
        [$user, $student] = $this->studentContext();
        $simulation = $this->simulation('Hidden state simulation');
        $this->actingAs($user)->post(route('simulations.start', $simulation));
        $session = $student->simulationSessions()->sole();

        $this->actingAs($user)
            ->get(route('simulations.sessions.show', $session))
            ->assertOk()
            ->assertDontSee('expected_state');
    }

    public function test_simulation_event_factory_uses_the_challenge_session(): void
    {
        $event = SimulationEvent::factory()->create();

        $this->assertSame($event->challenge->simulation_session_id, $event->simulation_session_id);
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

    private function simulation(
        string $title,
        string $status = 'published',
        int $challengeCount = 2,
    ): Simulation {
        $skill = Skill::factory()->create();
        $simulation = Simulation::factory()->create([
            'title' => $title,
            'title_marathi' => $title,
            'status' => $status,
            'engine_key' => 'addition_objects',
            'configuration' => [
                'challenge_count' => $challengeCount,
                'max_attempts' => 3,
                'minimum_difficulty' => 1,
                'maximum_difficulty' => 5,
            ],
        ]);
        $simulation->skills()->attach($skill->id, ['weight' => 1]);

        return $simulation->load('skills');
    }
}
