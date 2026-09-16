<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GameSession>
 */
class GameSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_key' => (string) Str::uuid(),
            'game_id' => Game::factory(),
            'game_level_id' => null,
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'status' => 'in_progress',
            'difficulty' => 1,
            'started_at' => now(),
            'completed_at' => null,
            'expires_at' => now()->addMinute(),
            'server_state' => [
                'lives_total' => 3,
                'lives_remaining' => 3,
                'score' => 0,
                'answered_count' => 0,
                'correct_count' => 0,
                'incorrect_count' => 0,
                'question_started_at' => now()->toIso8601String(),
            ],
        ];
    }
}
