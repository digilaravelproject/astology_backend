<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendMessagePushNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(MessageSent $event): void
    {
        // Push notifications for individual chat messages are disabled to prevent spamming while users are chatting.
        // Real-time messages are delivered directly via WebSocket broadcasting.
        return;
    }
}
