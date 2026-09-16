<?php

namespace App\Models;

use Database\Factories\LearningRecommendationItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningRecommendationItem extends Model
{
    /** @use HasFactory<LearningRecommendationItemFactory> */
    use HasFactory;

    protected $fillable = [
        'learning_recommendation_id', 'position', 'item_type', 'resource_type',
        'resource_id', 'title', 'title_marathi', 'instructions', 'instructions_marathi',
    ];

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(LearningRecommendation::class, 'learning_recommendation_id');
    }
}
