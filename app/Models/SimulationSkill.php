<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulationSkill extends Model
{
    protected $fillable = ['simulation_id', 'skill_id', 'weight'];

    protected function casts(): array
    {
        return ['weight' => 'decimal:2'];
    }

    public function simulation(): BelongsTo
    {
        return $this->belongsTo(Simulation::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
