<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageSubSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_purchase_id',
        'mode',
        'chat_status',
        'call_status',
        'session_state',
        'chat_session_id',
        'call_session_id',
        'started_at',
        'ended_at',
        'duration_used',
        'last_heartbeat_user',
        'last_heartbeat_astrologer',
        'paused_at',
        'pause_duration_seconds',
    ];

    protected $casts = [
        'started_at'                => 'datetime',
        'ended_at'                  => 'datetime',
        'last_heartbeat_user'       => 'datetime',
        'last_heartbeat_astrologer' => 'datetime',
        'paused_at'                 => 'datetime',
        'duration_used'             => 'integer',
        'pause_duration_seconds'    => 'integer',
        'chat_session_id'           => 'integer',
        'call_session_id'           => 'integer',
    ];

    public function purchase()
    {
        return $this->belongsTo(PackagePurchase::class, 'package_purchase_id');
    }

    public function chatSession()
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }

    public function callSession()
    {
        return $this->belongsTo(CallSession::class, 'call_session_id');
    }

    /**
     * Determine active media type (none, chat_only, call_only, concurrent_both).
     */
    public function getActiveMediaAttribute(): string
    {
        $isChat = in_array($this->chat_status, ['active', 'held']);
        $isCall = in_array($this->call_status, ['connected', 'ringing']);

        if ($isChat && $isCall) {
            return 'concurrent_both';
        }
        if ($isCall) {
            return 'call_only';
        }
        if ($isChat) {
            return 'chat_only';
        }
        return 'none';
    }

    /**
     * Determine top routing priority for Flutter floating bar.
     */
    public function getActiveRoutePriorityAttribute(): string
    {
        if (in_array($this->call_status, ['connected', 'ringing'])) {
            return 'CALL';
        }
        if (in_array($this->chat_status, ['active', 'held'])) {
            return 'CHAT';
        }
        return 'NONE';
    }

    /**
     * Format payload for real-time WebSocket broadcast and floating banner API.
     */
    public function toBannerArray(int $remainingSeconds): array
    {
        $purchase = $this->purchase;
        $user = $purchase?->user;
        $astrologerUser = $purchase?->astrologer;
        $astrologer = $astrologerUser?->astrologer;

        return [
            'sub_session_id'        => $this->id,
            'purchase_id'           => $this->package_purchase_id,
            'astrologer_id'         => $purchase?->astrologer_id,
            'astrologer_name'       => $astrologerUser?->name ?? 'Astrologer',
            'astrologer_avatar'     => $astrologerUser?->profile_photo
                ? \App\Helpers\MediaHelper::getUrl($astrologerUser->profile_photo)
                : $astrologer?->profile_photo,
            'user_id'               => $purchase?->user_id,
            'user_name'             => $user?->name ?? 'User',
            'user_avatar'           => $user?->profile_photo ? \App\Helpers\MediaHelper::getUrl($user->profile_photo) : null,
            'remaining_seconds'     => $remainingSeconds,
            'session_state'         => $this->session_state,
            'chat_status'           => $this->chat_status,
            'call_status'           => $this->call_status,
            'active_media'          => $this->active_media,
            'active_route_priority' => $this->active_route_priority,
            'routing_context'       => [
                'chat_session_id' => $this->chat_session_id,
                'call_session_id' => $this->call_session_id,
                'call_channel_id' => $this->call_session_id ? 'call_' . $this->call_session_id : null,
                'can_resume_call' => in_array($this->call_status, ['connected', 'ringing', 'paused']),
                'can_resume_chat' => in_array($this->chat_status, ['active', 'held', 'paused']),
            ],
        ];
    }
}
