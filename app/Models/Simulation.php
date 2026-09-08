<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Simulation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'created_by', 'code', 'engine_key', 'title', 'title_marathi', 'description',
        'description_marathi', 'configuration', 'status',
    ];

    protected function casts(): array
    {
        return ['configuration' => 'array'];
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'simulation_skills')->withPivot('weight')->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(SimulationSession::class);
    }
}
