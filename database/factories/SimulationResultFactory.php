<?php

namespace Database\Factories;

use App\Models\SimulationResult;
use App\Models\SimulationSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SimulationResult>
 */
class SimulationResultFactory extends Factory
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
            'score' => 500,
            'max_score' => 500,
            'successful_count' => 5,
            'unsuccessful_count' => 0,
            'accuracy' => 100,
            'duration_seconds' => 60,
            'xp_awarded' => 20,
            'result_payload' => [],
            'validated_at' => now(),
        ];
    }
}
