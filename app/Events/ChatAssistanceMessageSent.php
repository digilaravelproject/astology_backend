<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatAssistanceMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageData;
    public $receiverId;

    public function __construct($messageData, $receiverId)
    {
        $this->messageData = $messageData;
        $this->receiverId = $receiverId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->receiverId),
        ];
    }

    public function broadcastAs()
    {
        return 'ChatAssistanceMessageSent';
    }

    public function broadcastWith(): array
    {
        return [
            'messageData' => [
                'id' => $this->messageData->id,
                'chat_assistance_session_id' => (int) $this->messageData->chat_assistance_session_id,
                'sender_id' => (int) $this->messageData->sender_id,
                'receiver_id' => (int) $this->messageData->receiver_id,
                'message' => $this->messageData->message,
                'attachment_url' => $this->messageData->attachment_url ? \App\Helpers\MediaHelper::getUrl($this->messageData->attachment_url) : null,
                'type' => $this->messageData->type,
                'is_read' => (bool) $this->messageData->is_read,
                'is_delivered' => (bool) $this->messageData->is_delivered,
                'call_session_id' => $this->messageData->call_session_id ? (int) $this->messageData->call_session_id : null,
                'created_at' => $this->messageData->created_at ? $this->messageData->created_at->toIso8601String() : null,
                'updated_at' => $this->messageData->updated_at ? $this->messageData->updated_at->toIso8601String() : null,
            ],
            'receiverId' => (int) $this->receiverId,
        ];
    }
}
