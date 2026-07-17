<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasLocalTimezoneSerialization;

class CallSession extends Model
{
    use HasLocalTimezoneSerialization;

    protected static function booted()
    {
        static::updated(function ($session) {
            if (in_array($session->status, ['missed', 'rejected', 'completed'])) {
                $subSession = \App\Models\PackageSubSession::where('call_session_id', $session->id)
                    ->whereNull('ended_at')
                    ->first();
                if ($subSession) {
                    try {
                        app(\App\Services\SessionTimerService::class)->endSubSession($subSession->id);
                    } catch (\Exception $e) {
                        // Swallow to avoid breaking standard transaction flow
                    }
                }
            }
        });
    }

    protected $fillable = [
        'consumer_id',
        'provider_id',
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
    ];

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
}
