<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolClass>
 */
class SchoolClassFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => 'Standard '.fake()->numberBetween(1, 7),
            'name_marathi' => 'इयत्ता',
            'grade_level' => fake()->unique()->numberBetween(1, 7),
            'is_active' => true,
        ];
    }
}
