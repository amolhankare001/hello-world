<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameSession extends Model
{
    protected $fillable = [
        'session_key', 'game_id', 'game_level_id', 'student_id', 'academic_year_id',
        'status', 'difficulty', 'started_at', 'completed_at', 'expires_at', 'server_state',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime', 'completed_at' => 'datetime', 'expires_at' => 'datetime',
            'server_state' => 'array',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(GameResult::class);
    }
}
