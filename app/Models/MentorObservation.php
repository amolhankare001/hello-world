<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentorObservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'mentor_id', 'academic_year_id', 'holistic_domain_id', 'observed_on',
        'category', 'observation', 'strengths', 'areas_for_improvement',
        'recommended_intervention', 'next_learning_goal', 'visibility',
    ];

    protected function casts(): array
    {
        return ['observed_on' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(HolisticDomain::class, 'holistic_domain_id');
    }
}
