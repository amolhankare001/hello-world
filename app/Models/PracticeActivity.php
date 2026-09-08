<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PracticeActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id', 'question_count', 'randomize_questions', 'show_feedback_immediately', 'configuration',
    ];

    protected function casts(): array
    {
        return [
            'randomize_questions' => 'boolean', 'show_feedback_immediately' => 'boolean', 'configuration' => 'array',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PracticeAttempt::class);
    }
}
