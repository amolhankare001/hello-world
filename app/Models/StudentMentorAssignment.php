<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMentorAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'mentor_id', 'academic_year_id', 'assigned_on', 'ended_on', 'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'assigned_on' => 'date',
            'ended_on' => 'date',
            'is_primary' => 'boolean',
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

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    #[Scope]
    protected function activeOn(Builder $query, string $date): Builder
    {
        return $query
            ->whereDate('assigned_on', '<=', $date)
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('ended_on')->orWhereDate('ended_on', '>=', $date);
            });
    }
}
