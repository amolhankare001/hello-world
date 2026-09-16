<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Skill;
use App\Models\SkillLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
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
            'skill_id' => Skill::factory(),
            'skill_level_id' => null,
            'created_by' => null,
            'code' => fake()->unique()->bothify('ACT-####-????'),
            'type' => 'learn',
            'title' => fake()->sentence(3),
            'title_marathi' => 'अध्ययन कृती',
            'instructions' => fake()->sentence(),
            'instructions_marathi' => 'सूचना वाचा आणि कृती पूर्ण करा.',
            'content' => ['body' => fake()->paragraph(), 'body_marathi' => 'अध्ययन मजकूर'],
            'difficulty' => 1,
            'estimated_minutes' => 10,
            'max_score' => 100,
            'status' => 'draft',
            'published_at' => null,
        ];
    }

    public function forLevel(SkillLevel $skillLevel): static
    {
        return $this->state(fn (): array => [
            'skill_id' => $skillLevel->skill_id,
            'skill_level_id' => $skillLevel->id,
        ]);
    }
}
