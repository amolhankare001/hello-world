<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolisticIndicator extends Model
{
    protected $fillable = [
        'holistic_domain_id', 'code', 'name', 'name_marathi', 'description', 'rating_scale',
        'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['rating_scale' => 'array', 'is_active' => 'boolean'];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(HolisticDomain::class, 'holistic_domain_id');
    }
}
