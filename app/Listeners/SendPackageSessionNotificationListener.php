<?php

namespace App\Listeners;

use App\Events\PackageSessionTerminated;
use App\Models\User;
use App\Services\Notification\PushNotificationPayload;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPackageSessionNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle package session exhaustion or timeout termination.
     *
     * @param PackageSessionTerminated $event
     */
    public function handle(PackageSessionTerminated $event): void
    {
        try {
            $purchase = $event->purchase;
            if (!$purchase) {
                return;
            }

            $consumerId = (int) $purchase->user_id;
            $providerId = (int) $purchase->astrologer_id;

            $consumer = User::find($consumerId);
            $provider = User::find($providerId);

            $userName = $consumer?->name ?? 'User';
            $astrologerName = $provider?->name ?? 'Astrologer';

            // 1. Notify Consumer User
            $userPayload = new PushNotificationPayload(
                title: 'Package Time Finished ⏱️',
                body: "Your prepaid package consultation with {$astrologerName} has ended.",
                type: 'package',
                referenceId: (string) $purchase->id,
                customData: [
                    'type'            => 'PACKAGE_EXHAUSTED',
                    'package_id'      => (string) $purchase->id,
                    'astrologer_id'   => (string) $providerId,
                    'astrologer_name' => $astrologerName,
                    'screen_route'    => '/package-status',
                    'click_action'    => 'FLUTTER_NOTIFICATION_CLICK',
                    'created_at'      => now()->toIso8601String(),
                ]
            );
            NotificationService::sendToUser($consumerId, $userPayload, saveInApp: true);

            // 2. Notify Astrologer
            $astroPayload = new PushNotificationPayload(
                title: 'Package Completed ⏱️',
                body: "Prepaid package session with {$userName} has completed.",
                type: 'package',
                referenceId: (string) $purchase->id,
                customData: [
                    'type'            => 'PACKAGE_EXHAUSTED',
                    'package_id'      => (string) $purchase->id,
                    'user_id'         => (string) $consumerId,
                    'user_name'       => $userName,
                    'screen_route'    => '/wallet',
                    'click_action'    => 'FLUTTER_NOTIFICATION_CLICK',
                    'created_at'      => now()->toIso8601String(),
                ]
            );
            NotificationService::sendToUser($providerId, $astroPayload, saveInApp: true);

        } catch (Throwable $e) {
            Log::error('SendPackageSessionNotificationListener error: ' . $e->getMessage());
        }
    }
}
