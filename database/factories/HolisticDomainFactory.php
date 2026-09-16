<?php

namespace Database\Factories;

use App\Models\HolisticDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HolisticDomain>
 */
class HolisticDomainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('DOMAIN_##??'),
            'name' => fake()->words(2, true),
            'name_marathi' => 'समग्र विकास',
            'description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}
