<?php

namespace Tests\Feature\Database;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Game;
use App\Models\GameLevel;
use App\Models\HolisticDomain;
use App\Models\HolisticIndicator;
use App\Models\Mentor;
use App\Models\PracticeActivity;
use App\Models\Question;
use App\Models\RecommendationRule;
use App\Models\Role;
use App\Models\School;
use App\Models\Simulation;
use App\Models\Skill;
use App\Models\SkillLevel;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentMentorAssignment;
use App\Models\StudentSkillProgress;
use App\Models\Subject;
use App\Models\Test;
use App\Models\User;
use App\Services\GameQuestionProvider;
use App\Services\SimulationChallengeProvider;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_creates_the_complete_demo_dataset(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(4, Role::query()->count());
        $this->assertSame(1, School::query()->count());
        $this->assertSame(1, AcademicYear::query()->count());
        $this->assertSame(1, Mentor::query()->count());
        $this->assertSame(15, Student::query()->count());
        $this->assertSame(15, StudentEnrollment::query()->count());
        $this->assertSame(15, StudentMentorAssignment::query()->count());
        $this->assertSame(2, Subject::query()->count());
        $this->assertSame(30, Skill::query()->count());
        $this->assertSame(90, SkillLevel::query()->count());
        $this->assertSame(32, Activity::query()->count());
        $this->assertSame(2, PracticeActivity::query()->count());
        $this->assertSame(6, Question::query()->count());
        $this->assertSame(3, Test::query()->count());
        $this->assertSame(21, Game::query()->count());
        $this->assertSame(63, GameLevel::query()->count());
        $this->assertSame(17, Simulation::query()->count());
        $this->assertSame(7, RecommendationRule::query()->count());
        $this->assertSame(5, HolisticDomain::query()->count());
        $this->assertSame(23, HolisticIndicator::query()->count());
        $this->assertSame(
            [
                'ADDITION_ADVENTURE',
                'AKSHAR_PAKDA',
                'BUILD_THE_WORD',
                'DIVISION_SHARING_GAME',
                'FIND_CORRECT_WORD',
                'FRACTION_PIZZA',
                'GREATER_OR_SMALLER',
                'LISTEN_AND_SELECT',
                'MATRA_BALLOONS',
                'MULTIPLICATION_SPACE_MISSION',
                'NUMBER_CATCH',
                'NUMBER_LINE_JUMP',
                'NUMBER_TRAIN',
                'PICTURE_WORD_MATCH',
                'PLACE_VALUE_HOUSE',
                'READING_CHALLENGE',
                'SENTENCE_MATCH',
                'SHOPPING_GAME',
                'SUBTRACTION_ADVENTURE',
                'WORD_ORDER',
                'WORD_TRAIN',
            ],
            Game::query()->orderBy('code')->pluck('code')->all(),
        );
        $this->assertSame(
            [
                'ADDITION_OBJECTS_SIMULATION',
                'BARAKHADI_BUILDER_SIMULATION',
                'CLOCK_SIMULATION',
                'DIVISION_SHARING_SIMULATION',
                'FRACTION_PIZZA_SIMULATION',
                'GEOMETRY_BUILDER_SIMULATION',
                'LETTER_JOINING_SIMULATION',
                'MATRA_CHANGE_SIMULATION',
                'MEASUREMENT_SIMULATION',
                'MONEY_SIMULATION',
                'MULTIPLICATION_ARRAYS_SIMULATION',
                'NUMBER_LINE_SIMULATION',
                'PICTURE_SENTENCE_SIMULATION',
                'PLACE_VALUE_BLOCKS_SIMULATION',
                'SENTENCE_BUILDING_SIMULATION',
                'SUBTRACTION_OBJECTS_SIMULATION',
                'WORD_BUILDING_SIMULATION',
            ],
            Simulation::query()->orderBy('code')->pluck('code')->all(),
        );
        $this->assertSame(450, StudentSkillProgress::query()->count());
        $this->assertSame(18, User::query()->count());
        $this->assertSame(
            RoleCode::SuperAdmin,
            User::query()->where('email', 'admin@example.test')->firstOrFail()->role->code
        );
    }

    public function test_every_seeded_game_generates_a_valid_server_question_round(): void
    {
        $this->seed(DatabaseSeeder::class);
        $questionProvider = app(GameQuestionProvider::class);

        Game::query()
            ->with(['levels' => fn ($query) => $query->orderBy('level'), 'skills'])
            ->orderBy('code')
            ->get()
            ->each(function (Game $game) use ($questionProvider): void {
                $questions = $questionProvider->generate($game, $game->levels->firstOrFail(), $game->skills);

                $this->assertCount(6, $questions, $game->code);
                foreach ($questions as $question) {
                    $this->assertContains(
                        data_get($question, 'expected_answer.value'),
                        collect($question['choices'])->pluck('value')->all(),
                        $game->code,
                    );
                }
            });
    }

    public function test_every_seeded_simulation_generates_a_valid_interactive_round(): void
    {
        $this->seed(DatabaseSeeder::class);
        $challengeProvider = app(SimulationChallengeProvider::class);

        Simulation::query()
            ->with('skills')
            ->orderBy('code')
            ->get()
            ->each(function (Simulation $simulation) use ($challengeProvider): void {
                $challenges = $challengeProvider->generate($simulation, 1, $simulation->skills);

                $this->assertCount(5, $challenges, $simulation->code);
                foreach ($challenges as $challenge) {
                    $this->assertNotEmpty(data_get($challenge, 'interaction.type'), $simulation->code);
                    $this->assertNotEmpty($challenge['expected_state'], $simulation->code);
                }
            });
    }
}
