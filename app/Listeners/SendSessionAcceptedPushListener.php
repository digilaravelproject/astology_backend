<?php

namespace App\Listeners;

use App\Events\CallAccepted;
use App\Events\ChatAccepted;
use App\Models\User;
use App\Services\Notification\PushNotificationPayload;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendSessionAcceptedPushListener
{

    /**
     * Handle incoming session acceptance events for Chat or Call.
     *
     * @param ChatAccepted|CallAccepted $event
     */
    public function handle(ChatAccepted|CallAccepted $event): void
    {
        try {
            $session = $event->session;
            if (!$session) {
                return;
            }

            $channelType = ($event instanceof CallAccepted) ? 'call' : 'chat';
            $consumerId = (int) $session->consumer_id;
            $providerId = (int) $session->provider_id;

            $astrologer = User::find($providerId);
            $astrologerName = $astrologer?->name ?? 'Astrologer';
            $astrologerAvatar = $astrologer?->profile_photo_url;

            $payload = PushNotificationPayload::forSessionAccepted(
                sessionId: (int) $session->id,
                channelType: $channelType,
                astrologerId: $providerId,
                astrologerName: $astrologerName,
                astrologerAvatar: $astrologerAvatar,
                extra: [
                    'rate_per_minute' => (string) ($session->rate_per_minute ?? 0),
                    'status'          => (string) ($session->status ?? 'ongoing'),
                ]
            );

            NotificationService::sendToUser($consumerId, $payload, saveInApp: true);

        } catch (Throwable $e) {
            Log::error('SendSessionAcceptedPushListener error: ' . $e->getMessage());
        }
    }
}
