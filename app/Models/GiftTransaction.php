<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'astrologer_id',
        'gift_id',
        'amount',
        'payment_provider',
        'provider_order_id',
        'provider_payment_id',
        'status',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function astrologer()
    {
        return $this->belongsTo(Astrologer::class, 'astrologer_id');
    }

    public function gift()
    {
        return $this->belongsTo(Gift::class);
    }
}
