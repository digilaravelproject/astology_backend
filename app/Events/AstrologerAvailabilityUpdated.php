<?php

namespace App\Events;

use App\Models\Astrologer;
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

    public function __construct(
        int $userId,
        bool $isOnline,
        bool $isBusy,
        ?int $busySessionId = null,
        ?string $busySessionType = null,
        ?int $astrologerId = null
    ) {
        $this->userId = $userId;
        $this->isOnline = $isOnline;
        $this->isBusy = $isBusy;
        $this->busySessionId = $busySessionId;
        $this->busySessionType = $busySessionType;

        // Resolve astrologer ID if not explicitly provided
        if (is_null($astrologerId)) {
            $astro = Astrologer::where('user_id', $userId)->first();
            $this->astrologerId = $astro?->id;
        } else {
            $this->astrologerId = $astrologerId;
        }

        // Status mapping: Engaged if busy, else Online or Offline
        $this->availabilityStatus = $isBusy ? 'Engaged' : ($isOnline ? 'Online' : 'Offline');
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
            'astrologer_id'       => $this->astrologerId,
            'user_id'             => $this->userId,
            'is_online'           => $this->isOnline,
            'is_busy'             => $this->isBusy,
            'availability_status' => $this->availabilityStatus, // "Engaged", "Online", "Offline"
            'busy_session_id'     => $this->busySessionId,
            'busy_session_type'   => $this->busySessionType,
            'timestamp'           => now()->toISOString(),
        ];
    }
}
