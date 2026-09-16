<?php

namespace Database\Factories;

use App\Models\GameQuestion;
use App\Models\GameSession;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameQuestion>
 */
class GameQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_session_id' => GameSession::factory(),
            'skill_id' => Skill::factory(),
            'sequence' => 1,
            'type' => 'choice',
            'prompt' => 'Catch A',
            'prompt_marathi' => 'A पकडा',
            'choices' => [
                ['value' => 'A', 'label' => 'A'],
                ['value' => 'B', 'label' => 'B'],
                ['value' => 'C', 'label' => 'C'],
            ],
            'expected_answer' => ['value' => 'A'],
            'presentation' => [],
            'difficulty' => 1,
            'max_score' => 100,
            'response_time_limit_ms' => 10000,
        ];
    }
}
