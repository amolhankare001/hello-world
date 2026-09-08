<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\HolisticIndicator;
use App\Models\HolisticRecord;
use App\Models\Mentor;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HolisticRecord>
 */
class HolisticRecordFactory extends Factory
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
            'mentor_id' => Mentor::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'holistic_indicator_id' => HolisticIndicator::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'notes' => fake()->sentence(),
            'recorded_at' => now(),
        ];
    }
}
