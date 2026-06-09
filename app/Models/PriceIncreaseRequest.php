<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceIncreaseRequest extends Model
{
    protected $fillable = [
        'astrologer_id',
        'level_id',
        'price_type',
        'old_price',
        'new_price',
        'increase_amount',
        'status',
        'admin_remark',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'increase_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function astrologer(): BelongsTo
    {
        return $this->belongsTo(Astrologer::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(PriceIncreaseLevel::class, 'level_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByAstrologer($query, $astrologerId)
    {
        return $query->where('astrologer_id', $astrologerId);
    }
}
