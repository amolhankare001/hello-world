<?php

namespace Database\Factories;

use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
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
            'code' => fake()->unique()->lexify('GAME-????'),
            'engine_key' => 'catch',
            'title' => 'Learning game',
            'title_marathi' => 'अध्ययन खेळ',
            'description' => 'A skill-based learning game.',
            'description_marathi' => 'कौशल्याधारित अध्ययन खेळ.',
            'configuration' => [
                'items' => [
                    ['value' => 'A', 'label' => 'A'],
                    ['value' => 'B', 'label' => 'B'],
                    ['value' => 'C', 'label' => 'C'],
                ],
            ],
            'status' => 'published',
        ];
    }
}
