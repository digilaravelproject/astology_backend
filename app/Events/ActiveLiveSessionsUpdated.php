<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActiveLiveSessionsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $action;
    public array $activeSessions;
    public ?array $session;

    public function __construct(string $action, array $activeSessions, ?array $session = null)
    {
        $this->action = $action;
        $this->activeSessions = $activeSessions;
        $this->session = $session;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('live-sessions'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ActiveLiveSessionsUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'action'          => $this->action,
            'session'         => $this->session,
            'active_sessions' => $this->activeSessions,
            'total_active'    => count($this->activeSessions),
            'timestamp'       => now()->toISOString(),
        ];
    }
}
