<?php

namespace App\Listeners;

use App\Events\ChatInitiated;
use App\Models\User;
use App\Services\Notification\PushNotificationPayload;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendChatInitiatedPushListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ChatInitiated $event): void
    {
        try {
            $session = $event->session;

            // Only notify provider if session is in 'initiated' state
            if (!$session || $session->status !== 'initiated') {
                return;
            }

            $providerId = (int) $session->provider_id;
            $consumerId = (int) $session->consumer_id;

            $senderName = $event->senderData['name'] ?? null;
            $senderAvatar = $event->senderData['profile_photo'] ?? null;

            if (!$senderName) {
                $consumer = User::find($consumerId);
                $senderName = $consumer?->name ?? 'A user';
                $senderAvatar = $consumer?->profile_photo_url;
            }

            $payload = PushNotificationPayload::forSessionRequest(
                sessionId: (int) $session->id,
                channelType: 'chat',
                userId: $consumerId,
                userName: $senderName,
                userAvatar: $senderAvatar,
                extra: [
                    'question'        => (string) ($session->question ?? ''),
                    'rate_per_minute' => (string) ($session->rate_per_minute ?? 0),
                ]
            );

            NotificationService::sendToUser($providerId, $payload, saveInApp: true);

        } catch (Throwable $e) {
            Log::error('SendChatInitiatedPushListener error: ' . $e->getMessage());
        }
    }
}
