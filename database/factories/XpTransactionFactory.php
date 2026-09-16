<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\XpTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<XpTransaction>
 */
class XpTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_key' => (string) Str::uuid(),
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'skill_id' => null,
            'source_type' => 'practice_attempt',
            'source_id' => fake()->unique()->numberBetween(1, 1000000),
            'points' => 15,
            'reason' => 'practice_completed',
            'metadata' => ['category' => 'completion'],
            'awarded_at' => now(),
        ];
    }
}
