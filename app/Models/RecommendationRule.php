<?php

namespace App\Models;

use Database\Factories\RecommendationRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecommendationRule extends Model
{
    /** @use HasFactory<RecommendationRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'title', 'title_marathi', 'signal', 'operator', 'threshold',
        'risk_level', 'minimum_events', 'guidance', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'threshold' => 'decimal:2',
            'guidance' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(LearningRecommendation::class);
    }
}
