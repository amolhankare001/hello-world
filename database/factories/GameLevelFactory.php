<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameLevel>
 */
class GameLevelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'level' => 1,
            'name' => 'Foundation',
            'name_marathi' => 'पायाभूत',
            'difficulty' => 1,
            'configuration' => [
                'question_count' => 5,
                'choice_count' => 3,
                'item_count' => 3,
                'lives' => 3,
                'response_time_seconds' => 10,
            ],
            'target_score' => 350,
            'time_limit_seconds' => 60,
        ];
    }
}
