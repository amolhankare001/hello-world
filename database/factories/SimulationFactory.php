<?php

namespace Database\Factories;

use App\Models\Simulation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Simulation>
 */
class SimulationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => null,
            'code' => fake()->unique()->regexify('[A-Z_]{12}'),
            'engine_key' => 'addition_objects',
            'title' => fake()->words(3, true),
            'title_marathi' => 'संवादात्मक अनुकरण',
            'description' => fake()->sentence(),
            'description_marathi' => 'वस्तू हलवून कृती पूर्ण करा.',
            'configuration' => [
                'challenge_count' => 5,
                'max_attempts' => 3,
            ],
            'status' => 'published',
        ];
    }
}
