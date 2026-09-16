<?php

namespace Database\Factories;

use App\Models\RecommendationRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecommendationRule>
 */
class RecommendationRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('[A-Z_]{16}'),
            'title' => 'Low skill accuracy',
            'title_marathi' => 'कौशल्य अचूकता कमी',
            'signal' => 'accuracy',
            'operator' => 'lt',
            'threshold' => 50,
            'risk_level' => 'red',
            'minimum_events' => 3,
            'guidance' => [],
            'sort_order' => 10,
            'is_active' => true,
        ];
    }
}
