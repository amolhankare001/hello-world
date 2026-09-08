<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillLevel extends Model
{
    protected $fillable = [
        'skill_id', 'level', 'name', 'name_marathi', 'learning_objective', 'mastery_threshold', 'configuration',
    ];

    protected function casts(): array
    {
        return ['mastery_threshold' => 'decimal:2', 'configuration' => 'array'];
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
