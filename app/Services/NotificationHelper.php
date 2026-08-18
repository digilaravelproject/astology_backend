<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Services\Notification\PushNotificationPayload;

class NotificationHelper
{
    /**
     * Create in-app notification and dispatch background push notification.
     *
     * @param int $userId
     * @param string $title
     * @param string $body
     * @param array $meta
     * @return AppNotification
     */
    public static function send(int $userId, string $title, string $body, array $meta = []): AppNotification
    {
        $type = $meta['type'] ?? 'system';
        $referenceId = $meta['reference_id'] ?? null;
        $imageUrl = $meta['image_url'] ?? null;

        $payload = PushNotificationPayload::forSystem(
            title: $title,
            body: $body,
            type: $type,
            referenceId: $referenceId,
            imageUrl: $imageUrl,
            extra: $meta
        );

        $notification = NotificationService::sendToUser($userId, $payload, saveInApp: true);

        return $notification ?? AppNotification::create([
            'user_id' => $userId,
            'title'   => $title,
            'body'    => $body,
            'meta'    => $meta,
            'is_read' => false,
        ]);
    }
}
