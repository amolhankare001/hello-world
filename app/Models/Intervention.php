<?php

namespace App\Models;

use Database\Factories\InterventionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Intervention extends Model
{
    /** @use HasFactory<InterventionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'learning_recommendation_id', 'student_id', 'mentor_id', 'academic_year_id',
        'skill_id', 'title', 'reason', 'plan', 'status', 'starts_on',
        'target_completion_on', 'completed_on', 'outcome',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date', 'target_completion_on' => 'date', 'completed_on' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(LearningRecommendation::class, 'learning_recommendation_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(InterventionActivity::class);
    }
}
