<?php

namespace Database\Factories;

use App\Models\ErrorType;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ErrorType>
 */
class ErrorTypeFactory extends Factory
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
            'code' => fake()->unique()->lexify('ERROR-????'),
            'name' => 'Concept error',
            'name_marathi' => 'संकल्पना चूक',
            'description' => 'The student needs concept reinforcement.',
            'remediation' => ['hint' => 'Review the worked example.'],
        ];
    }
}
