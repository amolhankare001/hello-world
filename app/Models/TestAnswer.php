<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestAnswer extends Model
{
    protected $fillable = [
        'test_attempt_id', 'question_id', 'question_option_id', 'error_type_id', 'answer',
        'is_correct', 'score', 'duration_seconds', 'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'answer' => 'array', 'is_correct' => 'boolean', 'score' => 'decimal:2', 'answered_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TestAttempt::class, 'test_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
