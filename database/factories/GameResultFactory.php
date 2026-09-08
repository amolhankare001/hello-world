<?php

namespace Database\Factories;

use App\Models\GameResult;
use App\Models\GameSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameResult>
 */
class GameResultFactory extends Factory
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
            'score' => 300,
            'max_score' => 500,
            'correct_count' => 3,
            'incorrect_count' => 2,
            'accuracy' => 60,
            'duration_seconds' => 30,
            'xp_awarded' => 20,
            'result_payload' => [],
            'validated_at' => now(),
        ];
    }
}
