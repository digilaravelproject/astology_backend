<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageIds;
    public $status;
    public $receiverId;
    public $sessionId;

    public function __construct($messageIds, $status, $receiverId, $sessionId)
    {
        $this->messageIds = $messageIds;
        $this->status = $status;
        $this->receiverId = $receiverId;
        $this->sessionId = $sessionId;
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('private-user.' . $this->receiverId)];
    }

    public function broadcastWith(): array
    {
        return [
            'message_ids' => $this->messageIds,
            'status' => $this->status,
            'session_id' => $this->sessionId
        ];
    }
}
