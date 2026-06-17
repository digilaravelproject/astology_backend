<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserJoinedLiveSession implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $liveSessionId;
    public array $payload;

    public function __construct(int $liveSessionId, array $payload)
    {
        $this->liveSessionId = $liveSessionId;
        $this->payload = $payload;
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('live-session.' . $this->liveSessionId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'UserJoinedLiveSession';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
