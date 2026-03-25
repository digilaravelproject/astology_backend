<?php

namespace App\Services;

use App\Models\AppNotification;

class NotificationHelper
{
    /**
     * Create notification.
     *
     * @param int $userId
     * @param string $title
     * @param string $body
     * @param array $meta
     * @return AppNotification
     */
    public static function send(int $userId, string $title, string $body, array $meta = []): AppNotification
    {
        return AppNotification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'meta' => $meta,
            'is_read' => false,
        ]);
    }
}
