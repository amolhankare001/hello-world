<?php

namespace Database\Factories;

use App\Models\Skill;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Skill>
 */
class SkillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'code' => fake()->unique()->lexify('SKILL-????'),
            'name' => fake()->words(2, true),
            'name_marathi' => 'कौशल्य',
            'sort_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}
