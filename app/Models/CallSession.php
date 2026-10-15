<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasLocalTimezoneSerialization;

class CallSession extends Model
{
    use HasLocalTimezoneSerialization;

    protected $fillable = [
        'consumer_id',
        'provider_id',
        'session_type',
        'live_session_id',
        'call_type',
        'status',
        'started_at',
        'ended_at',
        'duration_seconds',
        'rate_per_minute',
        'total_cost',
        'astrologer_earning',
        'admin_commission',
        'last_billed_at',
        'consumer_sdp',
        'provider_sdp',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'last_billed_at' => 'datetime',
        'rate_per_minute' => 'float',
        'total_cost' => 'float',
        'astrologer_earning' => 'float',
        'admin_commission' => 'float',
        'duration_seconds' => 'integer',
        'live_session_id' => 'integer',
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

    public function isLive(): bool
    {
        return $this->session_type === 'live';
    }

    public function consumer()
    {
        return $this->belongsTo(User::class, 'consumer_id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function iceCandidates()
    {
        return $this->hasMany(IceCandidate::class);
    }

    public function packageSubSession()
    {
        return $this->hasOne(PackageSubSession::class, 'call_session_id');
    }

    public function liveSession()
    {
        return $this->belongsTo(LiveSession::class, 'live_session_id');
    }
}
