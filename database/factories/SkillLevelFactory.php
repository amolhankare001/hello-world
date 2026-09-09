<?php

namespace Database\Factories;

use App\Models\Skill;
use App\Models\SkillLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SkillLevel>
 */
class SkillLevelFactory extends Factory
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
            'level' => fake()->numberBetween(1, 5),
            'name' => fake()->randomElement(['Foundation', 'Developing', 'Mastery']),
            'name_marathi' => fake()->randomElement(['पायाभूत', 'विकसनशील', 'प्रावीण्य']),
            'learning_objective' => fake()->sentence(),
            'mastery_threshold' => 80,
        ];
    }
}
