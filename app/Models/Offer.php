<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'discount_percentage',
        'call_astrologer_share',
        'call_admin_share',
        'chat_astrologer_share',
        'chat_admin_share',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'call_astrologer_share' => 'decimal:2',
        'call_admin_share' => 'decimal:2',
        'chat_astrologer_share' => 'decimal:2',
        'chat_admin_share' => 'decimal:2',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function astrologers()
    {
        return $this->belongsToMany(Astrologer::class, 'astrologer_offers')
            ->withPivot('id', 'status', 'activated_at', 'deactivated_at')
            ->withTimestamps();
    }
}
