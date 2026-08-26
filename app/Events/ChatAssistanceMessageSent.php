<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatAssistanceMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The message payload to broadcast.
     *
     * @var mixed
     */
    public $messageData;

    /**
     * The ID of the recipient user.
     *
     * @var int|string
     */
    public $receiverId;

    /**
     * Create a new event instance.
     *
     * @param mixed $messageData
     * @param int|string $receiverId
     */
    public function __construct($messageData, $receiverId)
    {
        $this->messageData = $messageData;
        $this->receiverId = $receiverId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        try {
            if (!empty($this->receiverId)) {
                $channels[] = new PrivateChannel('user.' . $this->receiverId);
            }

            $sessionId = is_array($this->messageData) 
                ? ($this->messageData['chat_assistance_session_id'] ?? null) 
                : ($this->messageData->chat_assistance_session_id ?? null);

            if (!empty($sessionId)) {
                $channels[] = new PrivateChannel('chat-assistance.' . $sessionId);
            }
        } catch (Throwable $e) {
            Log::error('Failed to resolve broadcast channels in ChatAssistanceMessageSent event: ' . $e->getMessage(), [
                'receiver_id' => $this->receiverId,
                'exception'   => $e,
            ]);

            if (!empty($this->receiverId)) {
                $channels = [new PrivateChannel('user.' . $this->receiverId)];
            }
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'ChatAssistanceMessageSent';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        try {
            return [
                'messageData' => [
                    'id' => $this->messageData->id,
                    'chat_assistance_session_id' => (int) $this->messageData->chat_assistance_session_id,
                    'sender_id' => (int) $this->messageData->sender_id,
                    'receiver_id' => (int) $this->messageData->receiver_id,
                    'message' => $this->messageData->message,
                    'attachment_url' => $this->messageData->attachment_url ? \App\Helpers\MediaHelper::getFullUrl($this->messageData->attachment_url) : null,
                    'type' => $this->messageData->type,
                    'is_read' => (bool) $this->messageData->is_read,
                    'is_delivered' => (bool) $this->messageData->is_delivered,
                    'call_session_id' => $this->messageData->call_session_id ? (int) $this->messageData->call_session_id : null,
                    'created_at' => $this->messageData->created_at ? $this->messageData->created_at->toIso8601String() : null,
                    'reply_to_id' => $this->messageData->reply_to_id ? (int) $this->messageData->reply_to_id : null,
                    'reply_to' => $this->messageData->replyTo ? [
                        'id' => (int) $this->messageData->replyTo->id,
                        'chat_assistance_session_id' => (int) $this->messageData->replyTo->chat_assistance_session_id,
                        'sender_id' => (int) $this->messageData->replyTo->sender_id,
                        'receiver_id' => (int) $this->messageData->replyTo->receiver_id,
                        'message' => $this->messageData->replyTo->message,
                        'attachment_url' => $this->messageData->replyTo->attachment_url ? \App\Helpers\MediaHelper::getFullUrl($this->messageData->replyTo->attachment_url) : null,
                        'type' => $this->messageData->replyTo->type,
                        'created_at' => $this->messageData->replyTo->created_at ? $this->messageData->replyTo->created_at->toIso8601String() : null,
                    ] : null,
                    'sender' => $this->messageData->sender ? [
                        'id' => $this->messageData->sender->id,
                        'name' => $this->messageData->sender->name,
                        'avatar' => $this->messageData->sender->profile_photo ? \App\Helpers\MediaHelper::getFullUrl($this->messageData->sender->profile_photo) : null,
                        'role' => $this->messageData->sender->role ?? 'user',
                    ] : null,
                ],
                'receiverId' => $this->receiverId,
            ];
        } catch (Throwable $e) {
            Log::error('Failed to format broadcastWith payload in ChatAssistanceMessageSent: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return [
                'messageData' => null,
                'receiverId'  => $this->receiverId,
            ];
        }
    }
}
