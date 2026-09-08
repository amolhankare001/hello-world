<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentSkillEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StudentSkillEvent>
 */
class StudentSkillEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_key' => (string) Str::uuid(),
            'student_id' => Student::factory(),
            'skill_id' => Skill::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'activity_id' => null,
            'error_type_id' => null,
            'activity_type' => 'practice',
            'source_type' => 'practice_attempt_question',
            'source_id' => null,
            'score' => 1,
            'max_score' => 1,
            'accuracy' => 100,
            'duration_seconds' => 10,
            'difficulty' => 1,
            'is_correct' => true,
            'xp_awarded' => 0,
            'metadata' => [],
            'occurred_at' => now(),
        ];
    }
}
