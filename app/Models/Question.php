<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'skill_id', 'activity_id', 'error_type_id', 'created_by', 'type', 'prompt', 'prompt_marathi',
        'audio_path', 'media_path', 'correct_answer', 'explanation', 'explanation_marathi',
        'difficulty', 'marks', 'is_active',
    ];

    protected function casts(): array
    {
        return ['correct_answer' => 'array', 'marks' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function errorType(): BelongsTo
    {
        return $this->belongsTo(ErrorType::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class);
    }
}
