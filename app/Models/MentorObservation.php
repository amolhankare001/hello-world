<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentorObservation extends Model
{
    protected $fillable = [
        'student_id', 'mentor_id', 'academic_year_id', 'holistic_domain_id', 'observed_on',
        'category', 'observation', 'visibility',
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
}
