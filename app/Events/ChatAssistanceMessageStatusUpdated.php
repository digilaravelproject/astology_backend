<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatAssistanceMessageStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageIds;
    public $status;
    public $receiverId;
    public $sessionId;
    public $updatedBy;
    public $timestamp;

    public function __construct(array $messageIds, string $status, int $receiverId, int $sessionId, int $updatedBy, string $timestamp)
    {
        $this->messageIds = $messageIds;
        $this->status = $status;
        $this->receiverId = $receiverId;
        $this->sessionId = $sessionId;
        $this->updatedBy = $updatedBy;
        $this->timestamp = $timestamp;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->receiverId),
        ];
    }

    public function broadcastAs()
    {
        return 'ChatAssistanceMessageStatusUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'messageIds' => $this->messageIds,
            'status' => $this->status,
            'receiverId' => (int) $this->receiverId,
            'sessionId' => (int) $this->sessionId,
            'updatedBy' => (int) $this->updatedBy,
            'timestamp' => $this->timestamp,
        ];
    }
}
