<?php

namespace App\Models;

use Database\Factories\LearningOutcomeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningOutcome extends Model
{
    /** @use HasFactory<LearningOutcomeFactory> */
    use HasFactory;

    protected $fillable = [
        'subject_id', 'skill_id', 'grade_level', 'code', 'statement', 'statement_marathi',
        'competency', 'competency_marathi', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'grade_level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
