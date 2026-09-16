<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\LearningRecommendation;
use App\Models\Skill;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningRecommendation>
 */
class LearningRecommendationFactory extends Factory
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
            'skill_id' => Skill::factory(),
            'recommendation_rule_id' => null,
            'risk_level' => 'yellow',
            'status' => LearningRecommendation::STATUS_PENDING,
            'metrics' => [
                'mastery' => 55,
                'accuracy' => 60,
                'event_count' => 4,
            ],
            'reason' => 'This skill needs additional practice.',
            'reason_marathi' => 'या कौशल्यासाठी अधिक सराव आवश्यक आहे.',
            'generated_at' => now(),
        ];
    }
}
