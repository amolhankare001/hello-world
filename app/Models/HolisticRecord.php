<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolisticRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'mentor_id', 'academic_year_id', 'holistic_indicator_id', 'rating', 'notes', 'recorded_at',
    ];

    protected function casts(): array
    {
        return ['rating' => 'decimal:2', 'recorded_at' => 'datetime'];
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

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(HolisticIndicator::class, 'holistic_indicator_id');
    }
}
