<?php

namespace App\Models;

use Database\Factories\SimulationEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulationEvent extends Model
{
    /** @use HasFactory<SimulationEventFactory> */
    use HasFactory;

    protected $fillable = [
        'simulation_session_id',
        'simulation_challenge_id',
        'attempt_number',
        'event_type',
        'payload',
        'is_success',
        'score',
        'response_ms',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'is_success' => 'boolean',
            'occurred_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(SimulationSession::class, 'simulation_session_id');
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(SimulationChallenge::class, 'simulation_challenge_id');
    }
}
