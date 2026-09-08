<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Badge extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id', 'code', 'name', 'name_marathi', 'description', 'description_marathi',
        'icon_path', 'criteria', 'xp_bonus', 'is_active',
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
}
