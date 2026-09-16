<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Badge;
use App\Models\Student;
use App\Models\StudentBadge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentBadge>
 */
class StudentBadgeFactory extends Factory
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
            'badge_id' => Badge::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'earned_at' => now(),
            'evidence' => [],
        ];
    }
}
