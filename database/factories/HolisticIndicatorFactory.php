<?php

namespace Database\Factories;

use App\Models\HolisticDomain;
use App\Models\HolisticIndicator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HolisticIndicator>
 */
class HolisticIndicatorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'holistic_domain_id' => HolisticDomain::factory(),
            'code' => fake()->unique()->bothify('INDICATOR_##??'),
            'name' => fake()->words(2, true),
            'name_marathi' => 'प्रगती निर्देशक',
            'description' => fake()->sentence(),
            'rating_scale' => [
                1 => ['en' => 'Beginning', 'mr' => 'सुरुवात'],
                2 => ['en' => 'Developing', 'mr' => 'विकसनशील'],
                3 => ['en' => 'Progressing', 'mr' => 'प्रगतीशील'],
                4 => ['en' => 'Proficient', 'mr' => 'निपुण'],
                5 => ['en' => 'Advanced', 'mr' => 'प्रगत'],
            ],
            'sort_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}
