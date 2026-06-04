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
    public $readerId;
    public $readAt;

    /**
     * Create a new event instance.
     *
     * @param array  $messageIds  IDs of messages whose status changed
     * @param string $status      New status: 'delivered' or 'seen'
     * @param int    $receiverId  User ID to receive this notification (the sender of original messages)
     * @param int    $sessionId   Chat session ID
     * @param int|null $readerId  User ID who read/received the messages
     * @param string|null $readAt ISO 8601 timestamp when messages were read
     */
    public function __construct($messageIds, $status, $receiverId, $sessionId, $readerId = null, $readAt = null)
    {
        $this->messageIds = $messageIds;
        $this->status = $status;
        $this->receiverId = $receiverId;
        $this->sessionId = $sessionId;
        $this->readerId = $readerId;
        $this->readAt = $readAt;
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->receiverId)];
    }

    public function broadcastAs(): string
    {
        return 'MessageStatusUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'message_ids' => $this->messageIds,
            'status' => $this->status,
            'session_id' => $this->sessionId,
            'reader_id' => $this->readerId,
            'read_at' => $this->readAt,
        ];
    }
}
