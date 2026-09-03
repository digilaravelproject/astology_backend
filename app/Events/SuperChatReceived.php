<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SuperChatReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $liveSessionId;
    public $superChat;

    public function __construct($liveSessionId, $superChat)
    {
        $this->liveSessionId = $liveSessionId;
        $this->superChat = $superChat;
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('live-session.' . $this->liveSessionId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'SuperChatReceived';
    }

    public function broadcastWith(): array
    {
        return [
            'id'           => $this->superChat['id'] ?? null,
            'user_id'      => $this->superChat['user_id'],
            'user_name'    => $this->superChat['user_name'],
            'name'         => $this->superChat['name'] ?? $this->superChat['user_name'],
            'sender_name'  => $this->superChat['sender_name'] ?? $this->superChat['user_name'],
            'user_avatar'  => $this->superChat['user_avatar'] ?? null,
            'amount'       => (float) $this->superChat['amount'],
            'message'      => $this->superChat['message'] ?? '',
            'is_gift'      => true,
            'gift'         => $this->superChat['gift'] ?? null,
            'gift_icon'    => $this->superChat['gift']['icon_url'] ?? null,
            'gift_photo'   => $this->superChat['gift']['icon_url'] ?? null,
            'created_at'   => $this->superChat['created_at'],
        ];
    }
}
