<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AstrologerPhoneNumber extends Model
{
    protected $fillable = [
        'astrologer_id',
        'country_code',
        'phone',
        'is_verified',
        'is_default',
        'otp',
        'otp_expires_at',
        'otp_verified_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_default' => 'boolean',
        'otp_expires_at' => 'datetime',
        'otp_verified_at' => 'datetime',
    ];

    public function astrologer()
    {
        return $this->belongsTo(Astrologer::class);
    }
}
