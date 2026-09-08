<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeAttempt extends Model
{
    protected $fillable = [
        'attempt_key', 'practice_activity_id', 'student_id', 'academic_year_id', 'status',
        'difficulty', 'started_at', 'completed_at', 'correct_count', 'incorrect_count',
        'score', 'accuracy', 'duration_seconds', 'answers',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime', 'completed_at' => 'datetime', 'score' => 'decimal:2',
            'accuracy' => 'decimal:2', 'answers' => 'array',
        ];
    }

    public function practiceActivity(): BelongsTo
    {
        return $this->belongsTo(PracticeActivity::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
