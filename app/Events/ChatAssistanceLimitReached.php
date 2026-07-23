<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatAssistanceLimitReached implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $astrologerId;
    public $message;

    public function __construct(int $astrologerId, string $message = 'Daily reply limit reached.')
    {
        $this->astrologerId = $astrologerId;
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->astrologerId),
        ];
    }

    public function broadcastAs()
    {
        return 'ChatAssistanceLimitReached';
    }

    public function broadcastWith(): array
    {
        return [
            'astrologerId' => (int) $this->astrologerId,
            'message' => $this->message,
        ];
    }
}
