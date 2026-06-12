<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallAccepted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $session;
    public $answer;

    public function __construct($session, $answer = null)
    {
        $this->session = $session;
        $this->answer = $answer ?? ($session->answer ?? null);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->session->consumer_id),
            new PrivateChannel('call.' . $this->session->id),
        ];
    }
    
    public function broadcastAs()
    {
        return 'CallAccepted';
    }

    public function broadcastWith(): array
    {
        return [
            'session' => [
                'id'              => $this->session->id,
                'consumer_id'     => $this->session->consumer_id,
                'provider_id'     => $this->session->provider_id,
                'status'          => $this->session->status,
                'rate_per_minute' => $this->session->rate_per_minute,
                'started_at'      => optional($this->session->started_at)?->toISOString(),
            ],
            'answer' => $this->answer,
        ];
    }
}
