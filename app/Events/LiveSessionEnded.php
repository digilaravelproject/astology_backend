<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveSessionEnded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $liveSession;

    public function __construct($liveSession)
    {
        $this->liveSession = $liveSession;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('live-sessions'),
            new \Illuminate\Broadcasting\PresenceChannel('live-session.' . $this->liveSession->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'LiveSessionEnded';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->liveSession->id,
            'astrologer_id' => $this->liveSession->astrologer_id,
            'title' => $this->liveSession->title,
            'status' => 'ended',
        ];
    }
}
