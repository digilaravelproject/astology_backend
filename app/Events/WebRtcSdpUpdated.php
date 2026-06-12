<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebRtcSdpUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $session;
    public $sdp;
    public $senderId;
    public $type;

    public function __construct($session, $sdp, $senderId, string $type)
    {
        $this->session  = $session;
        $this->sdp      = $sdp;
        $this->senderId = $senderId;
        $this->type     = $type;
    }

    public function broadcastOn(): array
    {
        $receiverId = ($this->senderId == $this->session->consumer_id)
            ? $this->session->provider_id
            : $this->session->consumer_id;

        return [
            new PrivateChannel('user.' . $receiverId),
            new PrivateChannel('call.' . $this->session->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'WebRtcSdpUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'session' => ['id' => $this->session->id],
            'sdp'     => $this->sdp,
            'type'    => $this->type,
            'senderId'=> $this->senderId,
        ];
    }
}
