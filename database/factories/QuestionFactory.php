<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'skill_id' => Skill::factory(),
            'activity_id' => null,
            'error_type_id' => null,
            'created_by' => null,
            'type' => 'number_input',
            'prompt' => 'What is two plus three?',
            'prompt_marathi' => 'दोन अधिक तीन किती?',
            'correct_answer' => ['value' => 5],
            'explanation' => 'Two plus three equals five.',
            'explanation_marathi' => 'दोन अधिक तीन बरोबर पाच.',
            'difficulty' => 1,
            'marks' => 1,
            'is_active' => true,
        ];
    }
}
