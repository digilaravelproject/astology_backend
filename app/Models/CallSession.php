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
        'duration_seconds' => 'integer',
        'live_session_id' => 'integer',
    ];

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
