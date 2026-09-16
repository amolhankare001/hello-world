<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
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
            'student_number' => fake()->unique()->bothify('STU-####'),
            'date_of_birth' => fake()->dateTimeBetween('-12 years', '-7 years'),
            'gender' => fake()->randomElement(['female', 'male', 'other']),
            'joined_on' => fake()->dateTimeBetween('-3 years', 'now'),
            'guardian_name' => fake()->name(),
            'guardian_phone' => fake()->numerify('9#########'),
            'accessibility_preferences' => [],
        ];
    }
}
