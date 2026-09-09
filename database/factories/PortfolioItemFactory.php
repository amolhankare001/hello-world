<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\PortfolioItem;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PortfolioItem>
 */
class PortfolioItemFactory extends Factory
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
            'uploaded_by' => User::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'skill_id' => null,
            'type' => fake()->randomElement([
                'pre_test',
                'post_test',
                'game',
                'practice',
                'simulation',
                'teacher_observation',
                'student_work',
                'certificate',
                'progress_report',
            ]),
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'disk' => 'local',
            'path' => 'portfolios/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(100, 100000),
            'is_private' => true,
            'occurred_on' => today(),
        ];
    }
}
