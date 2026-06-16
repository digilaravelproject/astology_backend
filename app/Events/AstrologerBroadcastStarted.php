<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AstrologerBroadcastStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $liveSession;

    public function __construct($liveSession)
    {
        $this->liveSession = $liveSession;
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('live-session.' . $this->liveSession->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'AstrologerBroadcastStarted';
    }

    public function broadcastWith(): array
    {
        return [
            'live_session_id' => $this->liveSession->id,
            'room_uuid' => $this->liveSession->room_uuid,
            'broadcast_started_at' => now()->toISOString(),
        ];
    }
}
