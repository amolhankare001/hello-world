<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\Test;
use App\Models\TestAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TestAttempt>
 */
class TestAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attempt_key' => (string) Str::uuid(),
            'test_id' => Test::factory(),
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'attempt_number' => 1,
            'status' => 'in_progress',
            'started_at' => now(),
            'submitted_at' => null,
            'expires_at' => now()->addMinutes(20),
            'score' => null,
            'max_score' => null,
            'percentage' => null,
            'accuracy' => null,
            'duration_seconds' => null,
            'diagnosis' => ['question_ids' => []],
        ];
    }
}
