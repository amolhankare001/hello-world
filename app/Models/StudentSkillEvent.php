<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSkillEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_key', 'student_id', 'skill_id', 'academic_year_id', 'activity_id',
        'error_type_id', 'activity_type', 'source_type', 'source_id', 'score',
        'max_score', 'accuracy', 'duration_seconds', 'difficulty', 'is_correct',
        'xp_awarded', 'metadata', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2', 'max_score' => 'decimal:2', 'accuracy' => 'decimal:2',
            'is_correct' => 'boolean', 'metadata' => 'array', 'occurred_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function errorType(): BelongsTo
    {
        return $this->belongsTo(ErrorType::class);
    }
}
