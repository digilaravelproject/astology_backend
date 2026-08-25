<?php

namespace App\Listeners;

use App\Events\MessageSent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendMessagePushNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * Push notifications for individual ongoing chat messages are intentionally disabled
     * to prevent notification spam while users are actively chatting. Real-time messages
     * are delivered directly via WebSocket broadcasting.
     *
     * @param MessageSent $event
     * @return void
     */
    public function handle(MessageSent $event): void
    {
        return;
    }
}
