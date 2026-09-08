<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id', 'level', 'name', 'name_marathi', 'difficulty', 'configuration',
        'target_score', 'time_limit_seconds',
    ];

    protected function casts(): array
    {
        return ['configuration' => 'array'];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
