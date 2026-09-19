<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveSessionCallStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $liveSessionId;
    public array $payload;

    /**
     * Create a new event instance.
     *
     * @param int $liveSessionId
     * @param array $payload
     */
    public function __construct(int $liveSessionId, array $payload)
    {
        $this->liveSessionId = $liveSessionId;
        $this->payload = $payload;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('live-session.' . $this->liveSessionId),
        ];
    }

    /**
     * Broadcast as a specific event name.
     */
    public function broadcastAs(): string
    {
        return 'LiveSessionCallStatusUpdated';
    }

    /**
     * Data to broadcast with the event.
     */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
