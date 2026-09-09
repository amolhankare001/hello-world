<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public function practiceAttempts(): HasMany
    {
        return $this->hasMany(PracticeAttempt::class);
    }

    public function testAttempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class);
    }

    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    public function simulationSessions(): HasMany
    {
        return $this->hasMany(SimulationSession::class);
    }

    public function learningRecommendations(): HasMany
    {
        return $this->hasMany(LearningRecommendation::class);
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }

    public function mentorObservations(): HasMany
    {
        return $this->hasMany(MentorObservation::class);
    }

    public function holisticRecords(): HasMany
    {
        return $this->hasMany(HolisticRecord::class);
    }

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class);
    }

    public function xpTransactions(): HasMany
    {
        return $this->hasMany(XpTransaction::class);
    }

    public function streaks(): HasMany
    {
        return $this->hasMany(Streak::class);
    }

    public function dailyGoals(): HasMany
    {
        return $this->hasMany(DailyGoal::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'student_badges')
            ->withPivot(['academic_year_id', 'earned_at', 'evidence'])
            ->withTimestamps();
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class);
    }
}
