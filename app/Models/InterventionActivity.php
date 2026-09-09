<?php

namespace App\Models;

use Database\Factories\InterventionActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterventionActivity extends Model
{
    /** @use HasFactory<InterventionActivityFactory> */
    use HasFactory;

    protected $fillable = [
        'intervention_id', 'activity_id', 'title', 'instructions', 'assigned_on', 'completed_on', 'mentor_notes',
    ];

    protected function casts(): array
    {
        return ['assigned_on' => 'date', 'completed_on' => 'date'];
    }

    public function intervention(): BelongsTo
    {
        return $this->belongsTo(Intervention::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
