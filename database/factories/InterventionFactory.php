<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Intervention;
use App\Models\Mentor;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Intervention>
 */
class InterventionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'learning_recommendation_id' => null,
            'student_id' => Student::factory(),
            'mentor_id' => Mentor::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'skill_id' => null,
            'title' => 'Focused learning support',
            'reason' => 'The student needs additional guided practice.',
            'plan' => 'Complete assigned activities and review progress.',
            'status' => 'planned',
            'starts_on' => today(),
            'target_completion_on' => today()->addWeeks(2),
        ];
    }
}
