<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Intervention extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id', 'mentor_id', 'academic_year_id', 'skill_id', 'title', 'reason', 'plan',
        'status', 'starts_on', 'target_completion_on', 'completed_on', 'outcome',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date', 'target_completion_on' => 'date', 'completed_on' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(InterventionActivity::class);
    }
}
