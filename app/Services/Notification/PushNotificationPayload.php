<?php

namespace App\Services\Notification;

class PushNotificationPayload
{
    public string $title;
    public string $body;
    public ?string $imageUrl;
    public string $type; // 'call', 'chat', 'wallet', 'system', 'promo', 'order', 'review'
    public ?string $referenceId;
    public ?string $clickAction;
    public string $sound;
    public string $priority; // 'high' or 'normal'
    public array $customData;
    public bool $isDataOnly; // true for background wake-up calls

    public function __construct(
        string $title = '',
        string $body = '',
        string $type = 'system',
        ?string $referenceId = null,
        ?string $imageUrl = null,
        ?string $clickAction = 'FLUTTER_NOTIFICATION_CLICK',
        string $sound = 'default',
        string $priority = 'high',
        array $customData = [],
        bool $isDataOnly = false
    ) {
        $this->title = $title;
        $this->body = $body;
        $this->type = $type;
        $this->referenceId = $referenceId ? (string) $referenceId : null;
        $this->imageUrl = $imageUrl;
        $this->clickAction = $clickAction ?? 'FLUTTER_NOTIFICATION_CLICK';
        $this->sound = $sound;
        $this->priority = $priority;
        $this->customData = $customData;
        $this->isDataOnly = $isDataOnly;
    }

    /**
     * Build a high-priority background wake-up notification for incoming calls.
     */
    public static function forCall(
        int $sessionId,
        int $callerId,
        string $callerName,
        ?string $callerAvatar = null,
        string $callType = 'audio',
        array $extra = []
    ): self {
        $data = array_merge([
            'type' => 'call',
            'session_id' => (string) $sessionId,
            'caller_id' => (string) $callerId,
            'caller_name' => $callerName,
            'caller_avatar' => $callerAvatar ?? '',
            'call_type' => $callType,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'created_at' => now()->toIso8601String(),
        ], $extra);

        return new self(
            title: "Incoming {$callType} call",
            body: "{$callerName} is calling you...",
            type: 'call',
            referenceId: (string) $sessionId,
            imageUrl: $callerAvatar,
            clickAction: 'FLUTTER_NOTIFICATION_CLICK',
            sound: 'call_ringtone',
            priority: 'high',
            customData: $data,
            isDataOnly: true // Data message enables custom high-priority incoming call screen in Flutter
        );
    }

    /**
     * Build a chat message preview notification.
     */
    public static function forChat(
        int $sessionId,
        int $senderId,
        string $senderName,
        string $messagePreview,
        ?string $senderAvatar = null,
        array $extra = []
    ): self {
        $data = array_merge([
            'type' => 'chat',
            'session_id' => (string) $sessionId,
            'sender_id' => (string) $senderId,
            'sender_name' => $senderName,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'created_at' => now()->toIso8601String(),
        ], $extra);

        return new self(
            title: $senderName,
            body: mb_strimwidth($messagePreview, 0, 120, '...'),
            type: 'chat',
            referenceId: (string) $sessionId,
            imageUrl: $senderAvatar,
            clickAction: 'FLUTTER_NOTIFICATION_CLICK',
            sound: 'default',
            priority: 'high',
            customData: $data,
            isDataOnly: false
        );
    }

    /**
     * Build a high-priority session request notification (Chat / Call).
     */
    public static function forSessionRequest(
        int $sessionId,
        string $channelType,
        int $userId,
        string $userName,
        ?string $userAvatar = null,
        array $extra = []
    ): self {
        $typeStr = strtoupper($channelType) . '_REQUEST';
        $channelLabel = ucfirst($channelType);

        $data = array_merge([
            'type'            => $typeStr,
            'session_id'      => (string) $sessionId,
            'channel_type'    => $channelType,
            'user_id'         => (string) $userId,
            'user_name'       => $userName,
            'user_avatar'     => $userAvatar ?? '',
            'screen_route'    => "/{$channelType}-request",
            'click_action'    => 'FLUTTER_NOTIFICATION_CLICK',
            'created_at'      => now()->toIso8601String(),
        ], $extra);

        return new self(
            title: "New {$channelLabel} Request 💬",
            body: "{$userName} has requested a {$channelType} consultation with you.",
            type: $channelType,
            referenceId: (string) $sessionId,
            imageUrl: $userAvatar,
            clickAction: 'FLUTTER_NOTIFICATION_CLICK',
            sound: 'default',
            priority: 'high',
            customData: $data,
            isDataOnly: false
        );
    }

    /**
     * Build a session acceptance notification to consumer.
     */
    public static function forSessionAccepted(
        int $sessionId,
        string $channelType,
        int $astrologerId,
        string $astrologerName,
        ?string $astrologerAvatar = null,
        array $extra = []
    ): self {
        $typeStr = strtoupper($channelType) . '_ACCEPTED';
        $channelLabel = ucfirst($channelType);

        $data = array_merge([
            'type'            => $typeStr,
            'session_id'      => (string) $sessionId,
            'channel_type'    => $channelType,
            'astrologer_id'   => (string) $astrologerId,
            'astrologer_name' => $astrologerName,
            'screen_route'    => "/{$channelType}-room",
            'click_action'    => 'FLUTTER_NOTIFICATION_CLICK',
            'created_at'      => now()->toIso8601String(),
        ], $extra);

        return new self(
            title: "{$astrologerName} Accepted! 🌟",
            body: "{$astrologerName} accepted your {$channelType} request. Tap to connect now.",
            type: $channelType,
            referenceId: (string) $sessionId,
            imageUrl: $astrologerAvatar,
            clickAction: 'FLUTTER_NOTIFICATION_CLICK',
            sound: 'default',
            priority: 'high',
            customData: $data,
            isDataOnly: false
        );
    }

    /**
     * Build a standardized session summary notification with financial metrics.
     */
    public static function forSessionEnded(
        int $sessionId,
        string $channelType,
        string $recipientRole, // 'user' or 'astrologer'
        string $title,
        string $body,
        array $sessionData = []
    ): self {
        $typeStr = strtoupper($channelType) . '_ENDED';

        $data = array_merge([
            'type'         => $typeStr,
            'session_id'   => (string) $sessionId,
            'channel_type' => $channelType,
            'screen_route' => '/session-summary',
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'created_at'   => now()->toIso8601String(),
        ], $sessionData);

        return new self(
            title: $title,
            body: $body,
            type: $channelType,
            referenceId: (string) $sessionId,
            imageUrl: null,
            clickAction: 'FLUTTER_NOTIFICATION_CLICK',
            sound: 'default',
            priority: 'high',
            customData: $data,
            isDataOnly: false
        );
    }

    /**
     * Build a standard system or transactional notification (wallet, order, review).
     */
    public static function forSystem(
        string $title,
        string $body,
        string $type = 'system',
        ?string $referenceId = null,
        ?string $imageUrl = null,
        array $extra = []
    ): self {
        $data = array_merge([
            'type' => $type,
            'reference_id' => $referenceId ? (string) $referenceId : '',
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'created_at' => now()->toIso8601String(),
        ], $extra);

        return new self(
            title: $title,
            body: $body,
            type: $type,
            referenceId: $referenceId,
            imageUrl: $imageUrl,
            clickAction: 'FLUTTER_NOTIFICATION_CLICK',
            sound: 'default',
            priority: 'high',
            customData: $data,
            isDataOnly: false
        );
    }

    /**
     * Build a Live Session notification payload with full Flutter deep-linking data contract.
     *
     * @param int $sessionId Live session ID
     * @param int $astrologerId Astrologer profile ID
     * @param string $astrologerName Astrologer display name
     * @param string|null $astrologerAvatar Astrologer photo URL
     * @param string $status Event state: 'live', 'scheduled', 'reminder'
     * @param string $title Custom alert title
     * @param string $body Custom alert body
     * @param string|null $channelName LiveKit / Room channel identifier
     * @param array $extra Additional custom parameters
     * @return self
     */
    public static function forLiveSession(
        int $sessionId,
        int $astrologerId,
        string $astrologerName,
        ?string $astrologerAvatar = null,
        string $status = 'live',
        string $title = '',
        string $body = '',
        ?string $channelName = null,
        array $extra = []
    ): self {
        $channel = $channelName ?: "live_session_{$sessionId}";
        
        $defaultTitle = match ($status) {
            'scheduled' => "New Live Session Scheduled! 📅",
            'reminder' => "Live Session Starting Soon! ⏰",
            default => "{$astrologerName} is Live Now! 🔴",
        };

        $defaultBody = match ($status) {
            'scheduled' => "{$astrologerName} has scheduled a live session. Don't miss it!",
            'reminder' => "{$astrologerName} is going live in a few minutes. Get ready!",
            default => "Join the live session now to interact directly and ask your questions.",
        };

        $data = array_merge([
            'click_action'      => 'FLUTTER_NOTIFICATION_CLICK',
            'screen'            => 'LIVE_STREAM_SCREEN',
            'screen_route'      => '/live-stream',
            'route'             => 'live_session',
            'session_id'        => (string) $sessionId,
            'live_session_id'   => (string) $sessionId,
            'id'                => (string) $sessionId,
            'astrologer_id'     => (string) $astrologerId,
            'astrologer_name'   => $astrologerName,
            'astrologer_avatar' => $astrologerAvatar ?? '',
            'channel_name'      => $channel,
            'room_uuid'         => $channel,
            'type'              => 'live_stream',
            'notification_type' => 'live_session',
            'status'            => $status,
            'created_at'        => now()->toIso8601String(),
        ], $extra);

        return new self(
            title: $title ?: $defaultTitle,
            body: $body ?: $defaultBody,
            type: 'live_stream',
            referenceId: (string) $sessionId,
            imageUrl: $astrologerAvatar,
            clickAction: 'FLUTTER_NOTIFICATION_CLICK',
            sound: 'default',
            priority: 'high',
            customData: $data,
            isDataOnly: false
        );
    }

    /**
     * Convert to standard array for storing in database logs.
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'reference_id' => $this->referenceId,
            'image_url' => $this->imageUrl,
            'click_action' => $this->clickAction,
            'sound' => $this->sound,
            'priority' => $this->priority,
            'custom_data' => $this->customData,
            'is_data_only' => $this->isDataOnly,
        ];
    }
}
