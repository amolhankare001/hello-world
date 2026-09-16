<?php

namespace Database\Factories;

use App\Models\Badge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Badge>
 */
class BadgeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => null,
            'subject_id' => null,
            'skill_id' => null,
            'code' => fake()->unique()->lexify('BADGE-????'),
            'name' => 'Learning badge',
            'name_marathi' => 'अध्ययन बॅज',
            'description' => 'Awarded for consistent learning.',
            'description_marathi' => 'सातत्यपूर्ण अध्ययनासाठी दिला जातो.',
            'icon_path' => null,
            'criteria' => ['activity_count' => 1],
            'xp_bonus' => 10,
            'rarity' => 'common',
            'is_active' => true,
        ];
    }
}
