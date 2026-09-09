<?php

namespace Database\Factories;

use App\Models\Subject;
use App\Models\Test;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Test>
 */
class TestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => null,
            'academic_year_id' => null,
            'school_class_id' => null,
            'subject_id' => Subject::factory(),
            'created_by' => null,
            'code' => fake()->unique()->bothify('TEST-####-????'),
            'type' => 'pre_test',
            'title' => 'Foundation assessment',
            'title_marathi' => 'पायाभूत चाचणी',
            'instructions' => 'Answer every question.',
            'instructions_marathi' => 'प्रत्येक प्रश्नाचे उत्तर द्या.',
            'duration_minutes' => 20,
            'difficulty' => 1,
            'question_count' => 10,
            'max_attempts' => 1,
            'passing_score' => 60,
            'shuffle_questions' => false,
            'status' => 'published',
            'available_from' => null,
            'available_until' => null,
        ];
    }
}
