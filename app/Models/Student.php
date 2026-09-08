<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'school_id', 'student_number', 'date_of_birth', 'gender', 'joined_on',
        'guardian_name', 'guardian_phone', 'accessibility_preferences',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joined_on' => 'date',
            'accessibility_preferences' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function mentorAssignments(): HasMany
    {
        return $this->hasMany(StudentMentorAssignment::class);
    }

    public function skillProgress(): HasMany
    {
        return $this->hasMany(StudentSkillProgress::class);
    }

    public function skillEvents(): HasMany
    {
        return $this->hasMany(StudentSkillEvent::class);
    }
}
