<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ViewerCountUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $liveSessionId;
    public $viewerCount;

    public function __construct($liveSessionId, $viewerCount)
    {
        $this->liveSessionId = $liveSessionId;
        $this->viewerCount = $viewerCount;
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('live-session.' . $this->liveSessionId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ViewerCountUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'live_session_id' => $this->liveSessionId,
            'viewer_count' => $this->viewerCount,
        ];
    }
}
