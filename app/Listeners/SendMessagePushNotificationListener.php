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
        try {
            $messageData = $event->messageData;
            $receiverId = (int) $event->receiverId;

            $senderId = is_array($messageData) ? ($messageData['sender_id'] ?? 0) : ($messageData->sender_id ?? 0);
            $sessionId = is_array($messageData) ? ($messageData['chat_session_id'] ?? 0) : ($messageData->chat_session_id ?? 0);
            $msgType = is_array($messageData) ? ($messageData['type'] ?? 'text') : ($messageData->type ?? 'text');
            $rawText = is_array($messageData) ? ($messageData['message'] ?? '') : ($messageData->message ?? '');

            $previewText = match ($msgType) {
                'image' => '📷 Sent an image',
                'audio', 'voice' => '🎤 Sent a voice message',
                'file', 'document' => '📎 Sent an attachment',
                default => !empty($rawText) ? $rawText : 'Sent you a new message',
            };

            $sender = User::find($senderId);
            $senderName = $sender?->name ?? 'New Message';
            $senderAvatar = $sender?->profile_photo_url;

            NotificationService::sendChatMessageNotification(
                receiverUserId: $receiverId,
                senderUserId: (int) $senderId,
                senderName: $senderName,
                messagePreview: $previewText,
                sessionId: (int) $sessionId,
                senderAvatar: $senderAvatar,
                extra: [
                    'msg_type' => $msgType,
                ]
            );

        } catch (Throwable $e) {
            Log::error('SendMessagePushNotificationListener error: ' . $e->getMessage());
        }
    }
}
