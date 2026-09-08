<?php

namespace Database\Factories;

use App\Models\LearningRecommendationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningRecommendationItem>
 */
class LearningRecommendationItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'learning_recommendation_id' => LearningRecommendation::factory(),
            'position' => 1,
            'item_type' => 'practice',
            'resource_type' => null,
            'resource_id' => null,
            'title' => 'Guided practice',
            'title_marathi' => 'मार्गदर्शित सराव',
            'instructions' => 'Complete a short supported practice.',
            'instructions_marathi' => 'मदतीसह छोटा सराव पूर्ण करा.',
        ];
    }
}
