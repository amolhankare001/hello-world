<?php

namespace App\Models;

use Database\Factories\SimulationChallengeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SimulationChallenge extends Model
{
    /** @use HasFactory<SimulationChallengeFactory> */
    use HasFactory;

    protected $fillable = [
        'simulation_session_id',
        'skill_id',
        'sequence',
        'prompt',
        'prompt_marathi',
        'interaction',
        'expected_state',
        'max_score',
    ];

    protected $hidden = [
        'expected_state',
    ];

    protected function casts(): array
    {
        return [
            'interaction' => 'array',
            'expected_state' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(SimulationSession::class, 'simulation_session_id');
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SimulationEvent::class);
    }
}
