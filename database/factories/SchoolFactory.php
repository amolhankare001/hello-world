<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('SCH-####'),
            'name' => fake()->company().' School',
            'name_marathi' => 'ज्ञानदीप विद्यालय',
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->numerify('9#########'),
            'timezone' => 'Asia/Kolkata',
            'is_active' => true,
        ];
    }
}
