<?php

namespace App\Listeners;

use App\Events\CallInitiated;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendCallPushNotificationListener
{

    public function handle(CallInitiated $event): void
    {
        try {
            $session = $event->session;

            // Only send incoming call push notification if status is initiated (direct ring)
            if ($session->status !== 'initiated') {
                return;
            }

            $receiverUserId = (int) $session->provider_id;
            $callerUserId = (int) $session->consumer_id;

            $callerName = $event->callerData['name'] ?? null;
            $callerAvatar = $event->callerData['profile_photo'] ?? null;

            if (!$callerName) {
                $caller = User::find($callerUserId);
                $callerName = $caller?->name ?? 'A user';
                $callerAvatar = $caller?->profile_photo_url;
            }

            $callType = $session->call_type ?? 'audio';

            NotificationService::sendCallNotification(
                receiverUserId: $receiverUserId,
                callerUserId: $callerUserId,
                callerName: $callerName,
                callerAvatar: $callerAvatar,
                sessionId: (int) $session->id,
                callType: $callType,
                extra: [
                    'rate_per_minute' => (string) ($session->rate_per_minute ?? 0),
                ]
            );

        } catch (Throwable $e) {
            Log::error('SendCallPushNotificationListener error: ' . $e->getMessage());
        }
    }
}
