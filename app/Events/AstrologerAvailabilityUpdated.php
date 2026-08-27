<?php

namespace App\Events;

use App\Models\Astrologer;
use App\Models\CallSession;
use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AstrologerAvailabilityUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ?int $astrologerId;
    public int $userId;
    public bool $isOnline;
    public bool $isBusy;
    public string $availabilityStatus;
    public ?int $busySessionId;
    public ?string $busySessionType;
    public bool $isChatEnabled = false;
    public bool $isCallEnabled = false;
    public bool $isVideoCallEnabled = false;

    public function __construct(
        int $userId,
        bool $isOnline,
        bool $isBusy,
        ?int $busySessionId = null,
        ?string $busySessionType = null,
        ?int $astrologerId = null,
        ?bool $isChatEnabled = null,
        ?bool $isCallEnabled = null,
        ?bool $isVideoCallEnabled = null
    ) {
        $this->userId = $userId;
        $this->busySessionId = $busySessionId;
        $this->busySessionType = $busySessionType;

        $astro = null;
        if (is_null($astrologerId)) {
            $astro = Astrologer::where('user_id', $userId)->first();
            $this->astrologerId = $astro?->id;
        } else {
            $this->astrologerId = $astrologerId;
            $astro = Astrologer::find($astrologerId);
        }

        if (is_null($isChatEnabled) || is_null($isCallEnabled) || is_null($isVideoCallEnabled)) {
            if ($astro) {
                $this->isChatEnabled = (bool) ($astro->is_chat_enabled ?? $astro->chat_enabled ?? false);
                $this->isCallEnabled = (bool) ($astro->is_call_enabled ?? $astro->call_enabled ?? false);
                $this->isVideoCallEnabled = (bool) ($astro->is_video_call_enabled ?? $astro->video_call_enabled ?? false);
            } else {
                $this->isChatEnabled = false;
                $this->isCallEnabled = false;
                $this->isVideoCallEnabled = false;
            }
        } else {
            $this->isChatEnabled = (bool) $isChatEnabled;
            $this->isCallEnabled = (bool) $isCallEnabled;
            $this->isVideoCallEnabled = (bool) $isVideoCallEnabled;
        }

        // Determine if there is an ongoing chat/call session if not explicitly busy
        if (!$isBusy) {
            $hasOngoingChat = ChatSession::where(function ($q) use ($userId) {
                    $q->where('consumer_id', $userId)->orWhere('provider_id', $userId);
                })
                ->whereIn('status', ['accepted', 'ongoing'])
                ->exists();

            $hasOngoingCall = CallSession::where(function ($q) use ($userId) {
                    $q->where('consumer_id', $userId)->orWhere('provider_id', $userId);
                })
                ->whereIn('status', ['ringing', 'accepted', 'ongoing'])
                ->exists();

            if ($hasOngoingChat || $hasOngoingCall) {
                $isBusy = true;
                if (!$this->busySessionType) {
                    $this->busySessionType = $hasOngoingChat ? 'chat' : 'call';
                }
            }
        }

        $this->isBusy = $isBusy;

        // Status mapping: Engaged if busy, else Online or Offline
        if ($isBusy) {
            $this->availabilityStatus = 'Engaged';
            $this->isOnline = true;
        } else {
            $channelsOnline = ($this->isChatEnabled || $this->isCallEnabled || $this->isVideoCallEnabled);
            $effectiveOnline = $isOnline && ($channelsOnline || ($astro && $astro->is_online));
            $this->isOnline = $effectiveOnline;
            $this->availabilityStatus = $effectiveOnline ? 'Online' : 'Offline';
        }
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('astrologers'),
            new Channel('astrologer-availability'),
            new PresenceChannel('presence-room'),
            new PrivateChannel('user.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'AstrologerAvailabilityUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'id'                     => $this->astrologerId,
            'astrologer_id'          => $this->astrologerId,
            'user_id'                => $this->userId,
            'is_online'              => (bool) $this->isOnline,
            'is_busy'                => (bool) $this->isBusy,
            'availability_status'    => $this->availabilityStatus, // "Engaged", "Online", "Offline"
            'status'                 => $this->availabilityStatus,
            'availability'           => $this->availabilityStatus,
            'is_chat_enabled'        => (bool) $this->isChatEnabled,
            'is_call_enabled'        => (bool) $this->isCallEnabled,
            'is_video_call_enabled'  => (bool) $this->isVideoCallEnabled,
            'chat_enabled'           => (bool) $this->isChatEnabled,
            'call_enabled'           => (bool) $this->isCallEnabled,
            'video_call_enabled'     => (bool) $this->isVideoCallEnabled,
            'busy_session_id'        => $this->busySessionId,
            'busy_session_type'      => $this->busySessionType,
            'timestamp'              => now()->toISOString(),
        ];
    }
}
