<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErrorType extends Model
{
    use HasFactory;

    protected $fillable = ['skill_id', 'code', 'name', 'name_marathi', 'description', 'remediation'];

    protected function casts(): array
    {
        return ['remediation' => 'array'];
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
