<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HolisticDomain extends Model
{
    protected $fillable = ['code', 'name', 'name_marathi', 'description', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(HolisticIndicator::class);
    }
}
