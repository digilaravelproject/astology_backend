<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PresenceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $presenceData;

    public function __construct($presenceData)
    {
        $this->presenceData = $presenceData;
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('presence-room'),
        ];
    }
    
    public function broadcastAs()
    {
        return 'PresenceUpdated';
    }
}
