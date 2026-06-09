<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceIncreaseLevel extends Model
{
    protected $fillable = [
        'name',
        'level_number',
        'required_busy_minutes',
        'max_increase_amount',
        'is_active',
    ];

    protected $casts = [
        'required_busy_minutes' => 'integer',
        'max_increase_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function requests(): HasMany
    {
        return $this->hasMany(PriceIncreaseRequest::class, 'level_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('level_number');
    }
}
