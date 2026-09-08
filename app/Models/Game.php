<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Game extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'created_by', 'code', 'engine_key', 'title', 'title_marathi', 'description',
        'description_marathi', 'thumbnail_path', 'configuration', 'status',
    ];

    protected function casts(): array
    {
        return ['configuration' => 'array'];
    }

    public function levels(): HasMany
    {
        return $this->hasMany(GameLevel::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'game_skills')->withPivot('weight')->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
