<?php

namespace App\Listeners;

use App\Events\CallDismissed;
use App\Events\ChatDismissed;
use App\Models\User;
use App\Services\Notification\PushNotificationPayload;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendSessionDismissedPushListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle dismiss, reject, cancel, or missed events for Chat or Call.
     *
     * @param ChatDismissed|CallDismissed $event
     */
    public function handle(ChatDismissed|CallDismissed $event): void
    {
        try {
            $session = $event->session;
            if (!$session) {
                return;
            }

            $channelType = ($event instanceof CallDismissed) ? 'call' : 'chat';
            $channelLabel = ucfirst($channelType);

            $consumerId = (int) $session->consumer_id;
            $providerId = (int) $session->provider_id;
            $dismissedById = $event->dismissedById ?? null;
            $reason = strtolower($event->reason ?? 'rejected');

            $consumer = User::find($consumerId);
            $provider = User::find($providerId);

            $userName = $consumer?->name ?? 'User';
            $astrologerName = $provider?->name ?? 'Astrologer';

            $commonData = [
                'type'              => strtoupper($channelType) . '_DISMISSED',
                'session_id'        => (string) $session->id,
                'channel_type'      => $channelType,
                'reason'            => $reason,
                'dismissed_by_id'   => (string) ($dismissedById ?? ''),
                'user_id'           => (string) $consumerId,
                'user_name'         => $userName,
                'astrologer_id'     => (string) $providerId,
                'astrologer_name'   => $astrologerName,
                'screen_route'      => '/home',
                'click_action'      => 'FLUTTER_NOTIFICATION_CLICK',
                'created_at'        => now()->toIso8601String(),
            ];

            // 1. Astrologer Rejected / Declined
            if ($reason === 'rejected' || $dismissedById == $providerId) {
                $userPayload = new PushNotificationPayload(
                    title: "{$channelLabel} Request Declined ❌",
                    body: "{$astrologerName} is currently busy / declined your {$channelType} request.",
                    type: $channelType,
                    referenceId: (string) $session->id,
                    customData: array_merge($commonData, ['dismissed_by_role' => 'astrologer'])
                );
                NotificationService::sendToUser($consumerId, $userPayload, saveInApp: true);
                return;
            }

            // 2. User Cancelled Request
            if ($reason === 'cancelled' || $dismissedById == $consumerId) {
                $astroPayload = new PushNotificationPayload(
                    title: "{$channelLabel} Request Cancelled ⚠️",
                    body: "{$userName} cancelled the {$channelType} request.",
                    type: $channelType,
                    referenceId: (string) $session->id,
                    customData: array_merge($commonData, ['dismissed_by_role' => 'user'])
                );
                NotificationService::sendToUser($providerId, $astroPayload, saveInApp: true);
                return;
            }

            // 3. System Timeout / Missed
            if (in_array($reason, ['missed', 'timeout', 'offline'])) {
                // Notify User
                $userPayload = new PushNotificationPayload(
                    title: "Missed {$channelLabel} ⏱️",
                    body: "{$astrologerName} was unable to connect. Your wallet balance was not charged.",
                    type: $channelType,
                    referenceId: (string) $session->id,
                    customData: array_merge($commonData, ['dismissed_by_role' => 'system'])
                );
                NotificationService::sendToUser($consumerId, $userPayload, saveInApp: true);

                // Notify Astrologer
                $astroPayload = new PushNotificationPayload(
                    title: "Missed {$channelLabel} Request ⏱️",
                    body: "You missed a {$channelType} request from {$userName}.",
                    type: $channelType,
                    referenceId: (string) $session->id,
                    customData: array_merge($commonData, ['dismissed_by_role' => 'system'])
                );
                NotificationService::sendToUser($providerId, $astroPayload, saveInApp: true);
            }

        } catch (Throwable $e) {
            Log::error('SendSessionDismissedPushListener error: ' . $e->getMessage());
        }
    }
}
