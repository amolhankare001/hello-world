<?php

namespace App\Models;

use Database\Factories\LearningRecommendationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LearningRecommendation extends Model
{
    /** @use HasFactory<LearningRecommendationFactory> */
    use HasFactory;

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_MODIFIED = 'modified';

    public const STATUS_PENDING = 'pending';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'student_id', 'academic_year_id', 'skill_id', 'recommendation_rule_id',
        'risk_level', 'status', 'metrics', 'reason', 'reason_marathi',
        'mentor_notes', 'reviewed_by', 'generated_at', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'metrics' => 'array',
            'generated_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(RecommendationRule::class, 'recommendation_rule_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LearningRecommendationItem::class)->orderBy('position');
    }

    public function intervention(): HasOne
    {
        return $this->hasOne(Intervention::class);
    }
}
