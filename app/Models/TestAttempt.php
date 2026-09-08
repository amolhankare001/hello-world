<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestAttempt extends Model
{
    protected $fillable = [
        'attempt_key', 'test_id', 'student_id', 'academic_year_id', 'attempt_number', 'status',
        'started_at', 'submitted_at', 'expires_at', 'score', 'max_score', 'accuracy',
        'duration_seconds', 'diagnosis',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime', 'submitted_at' => 'datetime', 'expires_at' => 'datetime',
            'score' => 'decimal:2', 'max_score' => 'decimal:2', 'accuracy' => 'decimal:2',
            'diagnosis' => 'array',
        ];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TestAnswer::class);
    }
}
