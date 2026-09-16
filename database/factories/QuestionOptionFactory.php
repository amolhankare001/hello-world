<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionOption>
 */
class QuestionOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'label' => fake()->word(),
            'label_marathi' => 'पर्याय',
            'media_path' => null,
            'is_correct' => false,
            'sort_order' => fake()->unique()->numberBetween(1, 60000),
        ];
    }
}
