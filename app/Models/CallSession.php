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
