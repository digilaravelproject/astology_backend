<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasLocalTimezoneSerialization;

class ChatSession extends Model
{
    use HasLocalTimezoneSerialization;

    protected static function booted()
    {
        static::updated(function ($session) {
            if (in_array($session->status, ['missed', 'rejected', 'completed', 'timeout'])) {
                $subSession = \App\Models\PackageSubSession::where('chat_session_id', $session->id)
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
        'status',
        'started_at',
        'accepted_at',
        'ended_at',
        'duration_seconds',
        'rate_per_minute',
        'total_cost',
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
        'rate_per_minute' => 'float',
    ];

    public function consumer()
    {
        return $this->belongsTo(User::class, 'consumer_id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
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
