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

            $sessionId = is_array($this->messageData)
                ? ($this->messageData['chat_session_id'] ?? null)
                : ($this->messageData->chat_session_id ?? null);

            if (!empty($sessionId)) {
                $channels[] = new PrivateChannel('chat.' . $sessionId);
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
                'messageData' => $this->messageData,
                'receiverId'  => $this->receiverId,
            ];
        } catch (Throwable $e) {
            Log::error('Failed to format broadcastWith payload in MessageSent event: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return [
                'messageData' => null,
                'receiverId'  => $this->receiverId,
            ];
        }
    }
}
