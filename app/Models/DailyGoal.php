<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'academic_year_id', 'goal_date', 'target_activities', 'completed_activities',
        'target_minutes', 'completed_minutes', 'target_xp', 'earned_xp', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['goal_date' => 'date', 'completed_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
