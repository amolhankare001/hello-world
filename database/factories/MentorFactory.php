<?php

namespace Database\Factories;

use App\Models\Mentor;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mentor>
 */
class MentorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'school_id' => School::factory(),
            'employee_number' => fake()->unique()->bothify('MEN-###'),
            'phone' => fake()->numerify('9#########'),
            'qualifications' => 'B.Ed.',
        ];
    }
}
