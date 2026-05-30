<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatEnded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $session;
    public $endedById;
    public $billing;

    public function __construct($session, $endedById)
    {
        $this->session = $session;
        $this->endedById = $endedById;

        $durationSeconds = (int) ($session->duration_seconds ?? 0);
        $totalCost = (float) ($session->total_cost ?? 0.00);

        $this->billing = [
            'duration_seconds' => $durationSeconds,
            'user_details' => [
                'duration_seconds' => $durationSeconds,
                'amount_deducted' => $totalCost,
            ],
            'astrologer_details' => [
                'duration_seconds' => $durationSeconds,
                'amount_added' => $totalCost,
            ],
        ];
    }

    public function broadcastOn(): array
    {
        $receiverId = ($this->endedById == $this->session->consumer_id) 
            ? $this->session->provider_id 
            : $this->session->consumer_id;

        return [
            new PrivateChannel('user.' . $receiverId),
        ];
    }
    
    public function broadcastAs()
    {
        return 'ChatEnded';
    }
}
