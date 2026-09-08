<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameResult extends Model
{
    protected $fillable = [
        'game_session_id', 'score', 'max_score', 'correct_count', 'incorrect_count',
        'accuracy', 'duration_seconds', 'xp_awarded', 'result_payload', 'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'accuracy' => 'decimal:2', 'result_payload' => 'array', 'validated_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }
}
