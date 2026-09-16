<?php

namespace Database\Factories;

use App\Models\LearningOutcome;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningOutcome>
 */
class LearningOutcomeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_id' => fn (array $attributes): int => Skill::query()
                ->findOrFail($attributes['skill_id'])
                ->subject_id,
            'skill_id' => Skill::factory(),
            'grade_level' => 4,
            'code' => fake()->unique()->bothify('LO4-??-###'),
            'statement' => fake()->sentence(),
            'statement_marathi' => 'विद्यार्थी अध्ययन निष्पत्ती साध्य करतो.',
            'competency' => fake()->words(2, true),
            'competency_marathi' => 'इयत्ता चौथी कौशल्य',
            'sort_order' => fake()->numberBetween(1, 30),
            'is_active' => true,
        ];
    }
}
