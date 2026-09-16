<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Badge extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id', 'subject_id', 'skill_id', 'code', 'name', 'name_marathi', 'description',
        'description_marathi', 'icon_path', 'criteria', 'xp_bonus', 'rarity', 'is_active',
    ];

    protected function casts(): array
    {
        return ['criteria' => 'array', 'is_active' => 'boolean'];
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_badges')
            ->withPivot(['academic_year_id', 'earned_at', 'evidence'])
            ->withTimestamps();
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
