<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallEnded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $session;
    public $endedById;

    public function __construct($session, $endedById)
    {
        $this->session = $session;
        $this->endedById = $endedById;
    }

    /**
     * Broadcast to BOTH participants AND the session channel so that
     * whoever ends the call — user or astrologer — the other side
     * is notified immediately without exception.
     */
    public function broadcastOn(): array
    {
        return [
            // Both participants always receive the termination signal
            new PrivateChannel('user.' . $this->session->consumer_id),
            new PrivateChannel('user.' . $this->session->provider_id),
            // Session-level channel for per-session listeners
            new PrivateChannel('call.' . $this->session->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'CallEnded';
    }

    /**
     * Include ended_by_role so Flutter clients know who terminated the session
     * without needing to compare IDs client-side.
     */
    public function broadcastWith(): array
    {
        $endedByRole = ($this->endedById == $this->session->consumer_id)
            ? 'user'
            : 'astrologer';

        return [
            'session'       => $this->session,
            'ended_by_id'   => $this->endedById,
            'ended_by_role' => $endedByRole,  // 'user' | 'astrologer'
        ];
    }
}
