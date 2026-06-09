<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallInitiated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $session;
    public $callerData;

    /**
     * Create a new event instance.
     */
    public function __construct($session, $callerData)
    {
        $this->session = $session;
        $this->callerData = $callerData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->session->provider_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'CallInitiated';
    }

    /**
     * Explicit payload so the frontend knows the call status
     * ('initiated' = direct ring, 'waiting' = queued behind busy astrologer).
     */
    public function broadcastWith(): array
    {
        return [
            'session' => [
                'id'              => $this->session->id,
                'consumer_id'     => $this->session->consumer_id,
                'provider_id'     => $this->session->provider_id,
                'status'          => $this->session->status, // 'initiated' or 'waiting'
                'rate_per_minute' => $this->session->rate_per_minute,
                'created_at'      => optional($this->session->created_at)?->toISOString(),
            ],
            'callerData' => $this->callerData,
        ];
    }
}
