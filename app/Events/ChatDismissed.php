<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatDismissed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $session;
    public $dismissedById;

    /**
     * Create a new event instance.
     */
    public function __construct($session, $dismissedById = null)
    {
        $this->session = $session;
        $this->dismissedById = $dismissedById;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        if (empty($this->dismissedById)) {
            // Dismissed by system timeout, broadcast to both participants
            return [
                new PrivateChannel('user.' . $this->session->consumer_id),
                new PrivateChannel('user.' . $this->session->provider_id),
            ];
        }

        // Broadcast to the other participant
        $receiverId = ($this->dismissedById == $this->session->consumer_id) 
            ? $this->session->provider_id 
            : $this->session->consumer_id;

        return [
            new PrivateChannel('user.' . $receiverId),
        ];
    }
    
    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'ChatDismissed';
    }
}
