<?php

namespace Database\Factories;

use App\Models\Intervention;
use App\Models\InterventionActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InterventionActivity>
 */
class InterventionActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'intervention_id' => Intervention::factory(),
            'activity_id' => null,
            'title' => 'Guided practice',
            'instructions' => 'Complete this activity with mentor support.',
            'assigned_on' => today(),
        ];
    }
}
