<?php

namespace Database\Factories;

use App\Models\SimulationChallenge;
use App\Models\SimulationSession;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SimulationChallenge>
 */
class SimulationChallengeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'simulation_session_id' => SimulationSession::factory(),
            'skill_id' => Skill::factory(),
            'sequence' => 1,
            'prompt' => 'Build the target value.',
            'prompt_marathi' => 'दिलेली संख्या तयार करा.',
            'interaction' => [
                'type' => 'counter',
                'minimum' => 0,
                'maximum' => 20,
                'object' => '🍎',
            ],
            'expected_state' => ['value' => 5],
            'max_score' => 100,
        ];
    }
}
