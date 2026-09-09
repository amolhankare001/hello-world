<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id', 'parent_skill_id', 'code', 'name', 'name_marathi', 'description', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_skill_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_skill_id');
    }

    public function levels(): HasMany
    {
        return $this->hasMany(SkillLevel::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function errorTypes(): HasMany
    {
        return $this->hasMany(ErrorType::class);
    }

    public function learningRecommendations(): HasMany
    {
        return $this->hasMany(LearningRecommendation::class);
    }
}
