<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSkillProgress extends Model
{
    protected $table = 'student_skill_progress';

    protected $fillable = [
        'student_id', 'skill_id', 'academic_year_id', 'current_level', 'mastery_score',
        'total_attempts', 'correct_attempts', 'accuracy', 'best_score', 'average_score',
        'practice_count', 'game_count', 'simulation_count', 'pre_test_score',
        'post_test_score', 'improvement', 'last_activity_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'mastery_score' => 'decimal:2', 'accuracy' => 'decimal:2', 'best_score' => 'decimal:2',
            'average_score' => 'decimal:2', 'pre_test_score' => 'decimal:2',
            'post_test_score' => 'decimal:2', 'improvement' => 'decimal:2',
            'last_activity_at' => 'datetime',
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
}
