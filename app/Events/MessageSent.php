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

class MessageSent implements ShouldBroadcastNow
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

            $senderId = is_array($this->messageData)
                ? ($this->messageData['sender_id'] ?? null)
                : ($this->messageData->sender_id ?? null);

            if (!empty($senderId) && (int) $senderId !== (int) $this->receiverId) {
                $channels[] = new PrivateChannel('user.' . $senderId);
            }

            $chatSessionId = is_array($this->messageData)
                ? ($this->messageData['chat_session_id'] ?? null)
                : ($this->messageData->chat_session_id ?? null);

            if (!empty($chatSessionId)) {
                $channels[] = new PrivateChannel('chat.' . $chatSessionId);
            }

            $assistanceSessionId = is_array($this->messageData)
                ? ($this->messageData['chat_assistance_session_id'] ?? null)
                : ($this->messageData->chat_assistance_session_id ?? null);

            if (!empty($assistanceSessionId)) {
                $channels[] = new PrivateChannel('chat-assistance.' . $assistanceSessionId);
                $channels[] = new PrivateChannel('chat_assistance.' . $assistanceSessionId);
            }
        } catch (Throwable $e) {
            Log::error('Failed to resolve broadcast channels in MessageSent event: ' . $e->getMessage(), [
                'receiver_id' => $this->receiverId,
                'exception'   => $e,
            ]);

            // Fallback safety to ensure event does not crash
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
        return 'MessageSent';
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
                'message'     => $this->messageData,
                'messageData' => $this->messageData,
                'receiverId'  => $this->receiverId,
            ];
        } catch (Throwable $e) {
            Log::error('Failed to format broadcastWith payload in MessageSent event: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return [
                'message'     => null,
                'messageData' => null,
                'receiverId'  => $this->receiverId,
            ];
        }
    }
}
