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
    public $action;
    public $user;

    public function __construct($liveSessionId, $viewerCount, ?string $action = null, ?array $user = null)
    {
        $this->liveSessionId = $liveSessionId;
        $this->viewerCount = $viewerCount;
        $this->action = $action;
        $this->user = $user;
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
        $data = [
            'live_session_id' => $this->liveSessionId,
            'viewer_count'    => $this->viewerCount,
        ];

        if ($this->action) {
            $data['action'] = $this->action;
        }

        if ($this->user) {
            $data['user'] = $this->user;
        }

        return $data;
    }
}
