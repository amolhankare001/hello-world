<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Simulation;
use App\Models\SimulationSession;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SimulationSession>
 */
class SimulationSessionFactory extends Factory
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
            'simulation_id' => Simulation::factory(),
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'status' => 'in_progress',
            'difficulty' => 1,
            'started_at' => now(),
            'state' => [
                'current_sequence' => 1,
                'score' => 0,
                'successful_count' => 0,
                'unsuccessful_count' => 0,
            ],
        ];
    }
}
