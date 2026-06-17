<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveSessionStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $liveSession;

    public function __construct($liveSession)
    {
        $this->liveSession = $liveSession;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('live-sessions'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'LiveSessionStarted';
    }

    public function broadcastWith(): array
    {
        $astrologerUser = $this->liveSession->astrologer?->user;
        return [
            'id' => $this->liveSession->id,
            'title' => $this->liveSession->title,
            'astrologer' => $astrologerUser ? [
                'id' => $astrologerUser->id,
                'name' => $astrologerUser->name,
                'profile_photo' => $astrologerUser->profile_photo ? \App\Helpers\MediaHelper::getUrl($astrologerUser->profile_photo) : null,
            ] : null,
            'viewer_count' => $this->liveSession->viewer_count,
            'is_broadcasting' => $this->liveSession->is_broadcasting,
        ];
    }
}
