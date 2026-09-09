<?php

namespace Database\Factories;

use App\Models\SimulationChallenge;
use App\Models\SimulationEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SimulationEvent>
 */
class SimulationEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'simulation_challenge_id' => SimulationChallenge::factory(),
            'simulation_session_id' => fn (array $attributes): int => SimulationChallenge::query()
                ->findOrFail($attributes['simulation_challenge_id'])
                ->simulation_session_id,
            'attempt_number' => 1,
            'event_type' => 'submission',
            'payload' => ['value' => 5],
            'is_success' => true,
            'score' => 100,
            'response_ms' => 2000,
            'occurred_at' => now(),
        ];
    }
}
