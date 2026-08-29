<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatDismissed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $session;
    public $dismissedById;
    public $reason;

    /**
     * Create a new event instance.
     */
    public function __construct($session, $dismissedById = null, ?string $reason = 'rejected')
    {
        $this->session       = $session;
        $this->dismissedById = $dismissedById;
        $this->reason        = $reason;
    }

    /**
     * Get the channels the event should broadcast on.
     * Broadcasts to both user channels and the chat room channel.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->session->consumer_id),
            new PrivateChannel('user.' . $this->session->provider_id),
            new PrivateChannel('chat.' . $this->session->id),
        ];
    }
    
    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'ChatDismissed';
    }

    /**
     * Get data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'session'       => [
                'id'          => $this->session->id,
                'consumer_id' => $this->session->consumer_id,
                'provider_id' => $this->session->provider_id,
                'status'      => $this->session->status,
                'ended_at'    => optional($this->session->ended_at)?->toISOString(),
            ],
            'dismissedById' => $this->dismissedById,
            'reason'        => $this->reason,
        ];
    }
}
