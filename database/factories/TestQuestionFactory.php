<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Test;
use App\Models\TestQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestQuestion>
 */
class TestQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'test_id' => Test::factory(),
            'question_id' => Question::factory(),
            'sort_order' => 1,
            'marks' => 1,
        ];
    }
}
