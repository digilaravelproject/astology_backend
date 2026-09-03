<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewLiveComment implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $liveSessionId;
    public $comment;

    public function __construct($liveSessionId, $comment)
    {
        $this->liveSessionId = $liveSessionId;
        $this->comment = $comment;
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('live-session.' . $this->liveSessionId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'NewLiveComment';
    }

    public function broadcastWith(): array
    {
        return [
            'id'           => $this->comment['id'] ?? null,
            'user_id'      => $this->comment['user_id'],
            'user_name'    => $this->comment['user_name'],
            'name'         => $this->comment['name'] ?? $this->comment['user_name'],
            'sender_name'  => $this->comment['sender_name'] ?? $this->comment['user_name'],
            'user_avatar'  => $this->comment['user_avatar'] ?? null,
            'message'      => $this->comment['message'],
            'is_gift'      => $this->comment['is_gift'] ?? false,
            'gift'         => $this->comment['gift'] ?? null,
            'created_at'   => $this->comment['created_at'],
        ];
    }
}
