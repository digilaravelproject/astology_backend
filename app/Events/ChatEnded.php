<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $session;
    public $endedById;

    public function __construct($session, $endedById)
    {
        $this->session = $session;
        $this->endedById = $endedById;
    }

    public function broadcastOn(): array
    {
        $receiverId = ($this->endedById == $this->session->consumer_id) 
            ? $this->session->provider_id 
            : $this->session->consumer_id;

        return [
            new PrivateChannel('user.' . $receiverId),
        ];
    }
    
    public function broadcastAs()
    {
        return 'ChatEnded';
    }
}
