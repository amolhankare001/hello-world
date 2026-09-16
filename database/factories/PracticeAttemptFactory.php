<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\PracticeActivity;
use App\Models\PracticeAttempt;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PracticeAttempt>
 */
class PracticeAttemptFactory extends Factory
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
            'practice_activity_id' => PracticeActivity::factory(),
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'status' => 'in_progress',
            'difficulty' => 1,
            'started_at' => now(),
            'completed_at' => null,
            'correct_count' => 0,
            'incorrect_count' => 0,
            'score' => null,
            'accuracy' => null,
            'duration_seconds' => null,
            'answers' => ['question_ids' => []],
        ];
    }
}
