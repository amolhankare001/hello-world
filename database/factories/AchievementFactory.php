<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Achievement>
 */
class AchievementFactory extends Factory
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
            'achievement_key' => fake()->unique()->lexify('achievement-????'),
            'title' => 'Learning milestone',
            'title_marathi' => 'अध्ययन टप्पा',
            'description' => 'A personal learning milestone.',
            'metadata' => [],
            'achieved_at' => now(),
        ];
    }
}
