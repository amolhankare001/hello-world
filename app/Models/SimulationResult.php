<?php

namespace App\Models;

use Database\Factories\SimulationResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulationResult extends Model
{
    /** @use HasFactory<SimulationResultFactory> */
    use HasFactory;

    protected $fillable = [
        'simulation_session_id',
        'score',
        'max_score',
        'successful_count',
        'unsuccessful_count',
        'accuracy',
        'duration_seconds',
        'xp_awarded',
        'result_payload',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'accuracy' => 'decimal:2',
            'result_payload' => 'array',
            'validated_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(SimulationSession::class, 'simulation_session_id');
    }
}
