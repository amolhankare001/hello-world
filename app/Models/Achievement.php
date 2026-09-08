<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Achievement extends Model
{
    protected $fillable = [
        'student_id', 'academic_year_id', 'achievement_key', 'title', 'title_marathi',
        'description', 'metadata', 'achieved_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'achieved_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
