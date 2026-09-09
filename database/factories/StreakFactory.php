<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Streak;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Streak>
 */
class StreakFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'current_days' => 0,
            'longest_days' => 0,
            'last_activity_on' => null,
            'available_freezes' => 0,
        ];
    }
}
