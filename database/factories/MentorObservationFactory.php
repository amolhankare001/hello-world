<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\HolisticDomain;
use App\Models\Mentor;
use App\Models\MentorObservation;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MentorObservation>
 */
class MentorObservationFactory extends Factory
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
            'holistic_domain_id' => HolisticDomain::factory(),
            'observed_on' => today(),
            'category' => 'learning',
            'observation' => fake()->paragraph(),
            'strengths' => fake()->sentence(),
            'areas_for_improvement' => fake()->sentence(),
            'recommended_intervention' => fake()->sentence(),
            'next_learning_goal' => fake()->sentence(),
            'visibility' => 'school_team',
        ];
    }
}
