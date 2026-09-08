<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulationSession extends Model
{
    protected $fillable = [
        'session_key', 'simulation_id', 'student_id', 'academic_year_id', 'status',
        'difficulty', 'started_at', 'completed_at', 'duration_seconds', 'score',
        'accuracy', 'state', 'learning_events',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime', 'completed_at' => 'datetime', 'score' => 'decimal:2',
            'accuracy' => 'decimal:2', 'state' => 'array', 'learning_events' => 'array',
        ];
    }

    public function simulation(): BelongsTo
    {
        return $this->belongsTo(Simulation::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
