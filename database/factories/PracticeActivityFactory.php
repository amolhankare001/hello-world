<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\PracticeActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PracticeActivity>
 */
class PracticeActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory()->state(['type' => 'practice']),
            'question_count' => 10,
            'randomize_questions' => true,
            'show_feedback_immediately' => true,
            'configuration' => [
                'difficulty_up_accuracy' => 80,
                'remedial_accuracy' => 60,
                'minimum_difficulty' => 1,
                'maximum_difficulty' => 5,
            ],
        ];
    }
}
