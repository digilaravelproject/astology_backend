<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserForceLoggedOut implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public string $reason;
    public string $newDeviceId;

    /**
     * Create a new event instance.
     */
    public function __construct(int $userId, string $reason = 'logged_in_on_another_device', string $newDeviceId = '')
    {
        $this->userId = $userId;
        $this->reason = $reason;
        $this->newDeviceId = $newDeviceId;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->userId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'UserForceLoggedOut';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'user_id'       => $this->userId,
            'reason'        => $this->reason,
            'new_device_id' => $this->newDeviceId,
            'message'       => 'Your account was logged in on another device. Please log in again.',
            'logged_out_at' => now()->toIso8601String(),
        ];
    }
}
