<?php

namespace App\Services;

use App\Jobs\SendPushNotificationJob;
use App\Models\AppNotification;
use App\Models\BroadcastNotification;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Notification\PushNotificationPayload;
use Illuminate\Support\Facades\Log;
use Exception;

class NotificationService
{
    /**
     * Send push notification and save in-app notification for a single user.
     *
     * @param int $userId
     * @param PushNotificationPayload $payload
     * @param bool $saveInApp
     * @return AppNotification|null
     */
    public static function sendToUser(int $userId, PushNotificationPayload $payload, bool $saveInApp = true): ?AppNotification
    {
        $appNotification = null;

        if ($saveInApp && !$payload->isDataOnly) {
            try {
                $appNotification = AppNotification::create([
                    'user_id' => $userId,
                    'title'   => $payload->title,
                    'body'    => $payload->body,
                    'meta'    => array_merge($payload->customData, [
                        'type'         => $payload->type,
                        'reference_id' => $payload->referenceId,
                        'image_url'    => $payload->imageUrl,
                        'click_action' => $payload->clickAction,
                    ]),
                    'is_read' => false,
                ]);
            } catch (Exception $e) {
                Log::error("NotificationService: Failed to save in-app notification: " . $e->getMessage());
            }
        }

        // Fetch active FCM tokens for this user
        $tokens = UserDevice::forUser($userId)
            ->active()
            ->pluck('fcm_token')
            ->filter()
            ->toArray();

        // If user has legacy fcm_token on users table and no active device registered yet, fallback to it
        if (empty($tokens)) {
            $user = User::find($userId);
            if ($user && !empty($user->fcm_token)) {
                $tokens = [$user->fcm_token];
            }
        }

        if (!empty($tokens)) {
            SendPushNotificationJob::dispatch($tokens, $payload);
        }

        return $appNotification;
    }

    /**
     * Send push notification to multiple users.
     *
     * @param array $userIds
     * @param PushNotificationPayload $payload
     * @param bool $saveInApp
     */
    public static function sendToUsers(array $userIds, PushNotificationPayload $payload, bool $saveInApp = true): void
    {
        $userIds = array_unique(array_filter($userIds));
        if (empty($userIds)) {
            return;
        }

        // Bulk insert in-app notifications if needed
        if ($saveInApp && !$payload->isDataOnly) {
            $now = now();
            $records = [];
            $metaJson = json_encode(array_merge($payload->customData, [
                'type'         => $payload->type,
                'reference_id' => $payload->referenceId,
                'image_url'    => $payload->imageUrl,
                'click_action' => $payload->clickAction,
            ]));

            foreach ($userIds as $uid) {
                $records[] = [
                    'user_id'    => $uid,
                    'title'      => $payload->title,
                    'body'       => $payload->body,
                    'meta'       => $metaJson,
                    'is_read'    => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            try {
                // Chunk to prevent MySQL placeholder overflow
                foreach (array_chunk($records, 500) as $chunk) {
                    AppNotification::insert($chunk);
                }
            } catch (Exception $e) {
                Log::error("NotificationService: Bulk in-app insert error: " . $e->getMessage());
            }
        }

        // Fetch all active tokens from user_devices
        $tokens = UserDevice::whereIn('user_id', $userIds)
            ->active()
            ->pluck('fcm_token')
            ->filter()
            ->unique()
            ->toArray();

        // Fallback to legacy fcm_token on users table if any
        try {
            $legacyTokens = User::whereIn('id', $userIds)
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->pluck('fcm_token')
                ->filter()
                ->unique()
                ->toArray();
            $tokens = array_values(array_unique(array_filter(array_merge($tokens, $legacyTokens))));
        } catch (\Throwable $e) {
            $tokens = array_values(array_unique(array_filter($tokens)));
        }

        // Dispatch in chunks of 500 tokens
        if (!empty($tokens)) {
            foreach (array_chunk($tokens, 500) as $tokenChunk) {
                SendPushNotificationJob::dispatch($tokenChunk, $payload);
            }
        }
    }

    /**
     * Send high-priority Incoming Call wake-up notification to recipient (Astrologer or User).
     */
    public static function sendCallNotification(
        int $receiverUserId,
        int $callerUserId,
        string $callerName,
        ?string $callerAvatar = null,
        int $sessionId = 0,
        string $callType = 'audio',
        array $extra = []
    ): void {
        $payload = PushNotificationPayload::forCall(
            sessionId: $sessionId,
            callerId: $callerUserId,
            callerName: $callerName,
            callerAvatar: $callerAvatar,
            callType: $callType,
            extra: $extra
        );

        // Call alert is data-only (wake-up alert) so saveInApp is false
        self::sendToUser($receiverUserId, $payload, saveInApp: false);
    }

    /**
     * Send Chat message notification to recipient.
     */
    public static function sendChatMessageNotification(
        int $receiverUserId,
        int $senderUserId,
        string $senderName,
        string $messagePreview,
        int $sessionId = 0,
        ?string $senderAvatar = null,
        array $extra = []
    ): void {
        $payload = PushNotificationPayload::forChat(
            sessionId: $sessionId,
            senderId: $senderUserId,
            senderName: $senderName,
            messagePreview: $messagePreview,
            senderAvatar: $senderAvatar,
            extra: $extra
        );

        self::sendToUser($receiverUserId, $payload, saveInApp: true);
    }

    /**
     * Dispatch an Admin Broadcast campaign.
     */
    public static function sendBroadcast(BroadcastNotification $broadcast): void
    {
        $broadcast->update(['status' => 'processing']);

        $query = User::query();

        if ($broadcast->target_type === 'users') {
            $query->where('user_type', 'user');
        } elseif ($broadcast->target_type === 'astrologers') {
            $query->where('user_type', 'astrologer');
        } elseif ($broadcast->target_type === 'single_user' && $broadcast->target_user_id) {
            $query->where('id', $broadcast->target_user_id);
        }

        $userIds = $query->pluck('id')->toArray();
        $totalRecipients = count($userIds);

        $broadcast->update(['total_recipients' => $totalRecipients]);

        if ($totalRecipients === 0) {
            $broadcast->update(['status' => 'completed', 'successful_count' => 0, 'failed_count' => 0]);
            return;
        }

        $payload = new PushNotificationPayload(
            title: $broadcast->title,
            body: $broadcast->body,
            type: 'promo',
            imageUrl: $broadcast->image_url,
            clickAction: $broadcast->click_action ?? 'FLUTTER_NOTIFICATION_CLICK',
            customData: $broadcast->data_payload ?? []
        );

        // Bulk insert in-app notifications
        $now = now();
        $records = [];
        $metaJson = json_encode(array_merge($payload->customData, [
            'type'         => 'promo',
            'broadcast_id' => $broadcast->id,
            'image_url'    => $broadcast->image_url,
            'click_action' => $broadcast->clickAction,
        ]));

        foreach ($userIds as $uid) {
            $records[] = [
                'user_id'    => $uid,
                'title'      => $broadcast->title,
                'body'       => $broadcast->body,
                'meta'       => $metaJson,
                'is_read'    => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($records, 500) as $chunk) {
            AppNotification::insert($chunk);
        }

        // Fetch tokens and dispatch
        $tokens = UserDevice::whereIn('user_id', $userIds)
            ->active()
            ->pluck('fcm_token')
            ->filter()
            ->unique()
            ->toArray();

        if (empty($tokens)) {
            $broadcast->update(['status' => 'completed', 'successful_count' => 0, 'failed_count' => 0]);
            return;
        }

        foreach (array_chunk($tokens, 500) as $tokenChunk) {
            SendPushNotificationJob::dispatch($tokenChunk, $payload, $broadcast->id);
        }
    }
}
