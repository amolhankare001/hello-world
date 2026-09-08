<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XpTransaction extends Model
{
    protected $fillable = [
        'transaction_key', 'student_id', 'academic_year_id', 'skill_id', 'source_type',
        'source_id', 'points', 'reason', 'metadata', 'awarded_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'awarded_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
