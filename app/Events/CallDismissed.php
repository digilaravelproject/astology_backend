<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a call is rejected by astrologer, cancelled by user,
 * timed out by the system (missed), or auto-cancelled when either
 * party goes offline during ringing.
 *
 * Broadcasts to BOTH consumer and provider private channels so
 * either party can dismiss their ringing / incoming-call UI.
 */
class CallDismissed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $session;
    public $dismissedById; // null = system / timeout
    public $reason;        // 'rejected' | 'cancelled' | 'missed' | 'timeout'

    public function __construct($session, $dismissedById, string $reason = 'rejected')
    {
        $this->session       = $session;
        $this->dismissedById = $dismissedById;
        $this->reason        = $reason;
    }

    /**
     * Broadcast to both participants so either side can close its UI.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->session->consumer_id),
            new PrivateChannel('user.' . $this->session->provider_id),
            new PrivateChannel('call.' . $this->session->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'CallDismissed';
    }

    public function broadcastWith(): array
    {
        return [
            'session' => [
                'id'          => $this->session->id,
                'consumer_id' => $this->session->consumer_id,
                'provider_id' => $this->session->provider_id,
                'status'      => $this->session->status,
                'ended_at'    => optional($this->session->ended_at)?->toISOString(),
            ],
            'dismissedById' => $this->dismissedById,
            'reason'        => $this->reason,
        ];
    }
}
