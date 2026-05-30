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
        \Illuminate\Support\Facades\Log::info("ChatDismissed Event Triggered", [
            'session_id' => $this->session ? $this->session->id : 'NULL',
            'status' => $this->session ? $this->session->status : 'NULL',
            'dismissed_by' => $this->dismissedById,
            'consumer_id' => $this->session ? $this->session->consumer_id : 'NULL',
            'provider_id' => $this->session ? $this->session->provider_id : 'NULL',
        ]);

        if (empty($this->dismissedById)) {
            // Dismissed by system timeout, broadcast to both participants
            \Illuminate\Support\Facades\Log::info("ChatDismissed Broadcasting to both participants (System Timeout)");
            return [
                new PrivateChannel('user.' . $this->session->consumer_id),
                new PrivateChannel('user.' . $this->session->provider_id),
            ];
        }

        // Broadcast to the other participant
        $receiverId = ($this->dismissedById == $this->session->consumer_id) 
            ? $this->session->provider_id 
            : $this->session->consumer_id;

        \Illuminate\Support\Facades\Log::info("ChatDismissed Broadcasting to recipient", [
            'receiver_id' => $receiverId,
            'channel' => 'private-user.' . $receiverId
        ]);

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
