<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasLocalTimezoneSerialization;

class ChatSession extends Model
{
    use HasLocalTimezoneSerialization;

    protected $fillable = [
        'consumer_id',
        'provider_id',
        'session_type',
        'status',
        'started_at',
        'accepted_at',
        'ended_at',
        'duration_seconds',
        'rate_per_minute',
        'total_cost',
        'astrologer_earning',
        'admin_commission',
        'last_billed_at',
        'question',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'accepted_at' => 'datetime',
        'ended_at' => 'datetime',
        'last_billed_at' => 'datetime',
        'duration_seconds' => 'integer',
        'total_cost' => 'float',
        'astrologer_earning' => 'float',
        'admin_commission' => 'float',
        'rate_per_minute' => 'float',
    ];

    /**
     * Get astrologer net earning. Falls back to calculating based on Admin commission if not explicitly stored.
     */
    public function getAstrologerEarningAttribute($value): float
    {
        if ($value !== null && (float) $value > 0) {
            return (float) $value;
        }

        $totalCost = (float) ($this->attributes['total_cost'] ?? 0.00);
        if ($totalCost <= 0) {
            return 0.00;
        }

        $adminCommission = (float) (\App\Models\Setting::get('global_commission_percentage') ?? 40.00);
        $astroSharePct = max(0, min(100, 100 - $adminCommission));
        return round(($totalCost * $astroSharePct) / 100, 2);
    }

    public function isPrepaid(): bool
    {
        return $this->session_type === 'prepaid';
    }

    public function isNormal(): bool
    {
        return $this->session_type === 'normal' || empty($this->session_type);
    }

    public function consumer()
    {
        return $this->belongsTo(User::class, 'consumer_id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function packageSubSession()
    {
        return $this->hasOne(PackageSubSession::class, 'chat_session_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
}
