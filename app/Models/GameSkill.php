<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameSkill extends Model
{
    protected $fillable = ['game_id', 'skill_id', 'weight'];

    protected function casts(): array
    {
        return ['weight' => 'decimal:2'];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
