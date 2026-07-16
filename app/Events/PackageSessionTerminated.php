<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PackageSessionTerminated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $purchase;
    public $message;
    public $mode;

    /**
     * Create a new event instance.
     */
    public function __construct($purchase, string $message, string $mode)
    {
        $this->purchase = $purchase;
        $this->message = $message;
        $this->mode = $mode;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->purchase->user_id),
            new PrivateChannel('user.' . $this->purchase->astrologer_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PackageSessionTerminated';
    }
}
