<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\TestAnswer;
use App\Models\TestAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestAnswer>
 */
class TestAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'test_attempt_id' => TestAttempt::factory(),
            'question_id' => Question::factory(),
            'question_option_id' => null,
            'error_type_id' => null,
            'answer' => ['value' => null],
            'is_correct' => false,
            'score' => 0,
            'duration_seconds' => 1,
            'answered_at' => now(),
        ];
    }
}
