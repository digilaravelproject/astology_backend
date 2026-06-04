<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatQueueUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $providerId;
    public $session;
    public $action;

    public function __construct($providerId, $session = null, $action = 'updated')
    {
        $this->providerId = $providerId;
        $this->session = $session;
        $this->action = $action;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->providerId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ChatQueueUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'provider_id' => $this->providerId,
            'action' => $this->action,
            'session' => $this->session,
        ];
    }
}
