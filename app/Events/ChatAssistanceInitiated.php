<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatAssistanceInitiated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $session;
    public $senderData;

    public function __construct($session, $senderData)
    {
        $this->session = $session;
        $this->senderData = $senderData;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->session->provider_id),
        ];
    }

    public function broadcastAs()
    {
        return 'ChatAssistanceInitiated';
    }

    public function broadcastWith(): array
    {
        return [
            'session' => [
                'id' => $this->session->id,
                'consumer_id' => $this->session->consumer_id,
                'provider_id' => $this->session->provider_id,
                'created_at' => $this->session->created_at ? $this->session->created_at->toIso8601String() : null,
                'updated_at' => $this->session->updated_at ? $this->session->updated_at->toIso8601String() : null,
            ],
            'senderData' => [
                'id' => $this->senderData->id,
                'name' => $this->senderData->name,
                'profile_photo' => $this->senderData->profile_photo ? \App\Helpers\MediaHelper::getUrl($this->senderData->profile_photo) : null,
            ],
        ];
    }
}
