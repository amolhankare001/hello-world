<?php

namespace Database\Factories;

use App\Models\Division;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Division>
 */
class DivisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_class_id' => SchoolClass::factory(),
            'name' => fake()->randomElement(['A', 'B', 'C']),
            'name_marathi' => fake()->randomElement(['अ', 'ब', 'क']),
            'is_active' => true,
        ];
    }
}
