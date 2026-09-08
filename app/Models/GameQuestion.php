<?php

namespace App\Models;

use Database\Factories\GameQuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameQuestion extends Model
{
    /** @use HasFactory<GameQuestionFactory> */
    use HasFactory;

    protected $fillable = [
        'game_session_id',
        'skill_id',
        'sequence',
        'type',
        'prompt',
        'prompt_marathi',
        'choices',
        'expected_answer',
        'difficulty',
        'max_score',
        'response_time_limit_ms',
    ];

    protected $hidden = [
        'expected_answer',
    ];

    protected function casts(): array
    {
        return [
            'choices' => 'array',
            'expected_answer' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function answer(): HasOne
    {
        return $this->hasOne(GameAnswer::class);
    }
}
