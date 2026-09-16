<?php

namespace Database\Factories;

use App\Models\GameAnswer;
use App\Models\GameQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameAnswer>
 */
class GameAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_question_id' => GameQuestion::factory(),
            'answer' => ['value' => 'A'],
            'is_correct' => true,
            'response_ms' => 1000,
            'score' => 97,
            'answered_at' => now(),
        ];
    }
}
