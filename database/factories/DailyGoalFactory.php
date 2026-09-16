<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\DailyGoal;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyGoal>
 */
class DailyGoalFactory extends Factory
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
            'goal_date' => today(),
            'target_activities' => 3,
            'completed_activities' => 0,
            'target_minutes' => 15,
            'completed_minutes' => 0,
            'target_xp' => 50,
            'earned_xp' => 0,
            'completed_at' => null,
        ];
    }
}
