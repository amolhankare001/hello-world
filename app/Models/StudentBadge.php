<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentBadge extends Model
{
    protected $fillable = ['student_id', 'badge_id', 'academic_year_id', 'earned_at', 'evidence'];

    protected function casts(): array
    {
        return ['earned_at' => 'datetime', 'evidence' => 'array'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(Badge::class);
    }
}
