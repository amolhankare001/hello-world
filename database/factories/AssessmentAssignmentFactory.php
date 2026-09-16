<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\AssessmentAssignment;
use App\Models\Student;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentAssignment>
 */
class AssessmentAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'test_id' => Test::factory(),
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'assigned_by' => User::factory(),
            'status' => AssessmentAssignment::STATUS_ASSIGNED,
            'assigned_at' => now(),
            'started_at' => null,
            'completed_at' => null,
        ];
    }
}
