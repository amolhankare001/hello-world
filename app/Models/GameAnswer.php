<?php

namespace App\Models;

use Database\Factories\GameAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameAnswer extends Model
{
    /** @use HasFactory<GameAnswerFactory> */
    use HasFactory;

    protected $fillable = [
        'game_question_id',
        'answer',
        'is_correct',
        'response_ms',
        'score',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'answer' => 'array',
            'is_correct' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(GameQuestion::class, 'game_question_id');
    }
}
