<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Streak extends Model
{
    protected $fillable = [
        'student_id', 'academic_year_id', 'current_days', 'longest_days', 'last_activity_on', 'available_freezes',
    ];

    protected function casts(): array
    {
        return ['last_activity_on' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
