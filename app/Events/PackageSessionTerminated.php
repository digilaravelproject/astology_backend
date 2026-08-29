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
    public function __construct($purchase, string $message = 'Package session ended.', string $mode = 'chat')
    {
        if ($purchase instanceof \App\Models\PackageSubSession) {
            $this->purchase = $purchase->purchase ?? $purchase->load('purchase')->purchase;
            $this->mode = $purchase->mode ?? $mode;
        } else {
            $this->purchase = $purchase;
            $this->mode = $mode;
        }
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        if (!$this->purchase) {
            return [];
        }

        return [
            new PrivateChannel('user.' . $this->purchase->user_id),
            new PrivateChannel('user.' . $this->purchase->astrologer_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PackageSessionTerminated';
    }

    public function broadcastWith(): array
    {
        return [
            'purchase'            => $this->purchase,
            'package_purchase_id' => $this->purchase?->id,
            'mode'                => $this->mode,
            'message'             => $this->message,
            'remaining_duration'  => $this->purchase?->remaining_duration ?? 0,
            'package_status'      => $this->purchase?->status ?? 'exhausted',
        ];
    }
}
