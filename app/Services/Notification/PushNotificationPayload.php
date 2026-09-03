<?php

namespace App\Services\Notification;

class PushNotificationPayload
{
    public string $title;
    public string $body;
    public ?string $imageUrl;
    public string $type; // 'call', 'chat', 'wallet', 'system', 'promo', 'order', 'review', 'live_stream'
    public ?string $referenceId;
    public ?string $clickAction;
    public string $sound;
    public string $priority; // 'high' or 'normal'
    public array $customData;
    public bool $isDataOnly; // true for background wake-up calls

    // Canonical Enterprise Properties
    public ?string $entityType = null;
    public ?string $entityId = null;
    public ?string $action = null;
    public ?string $senderId = null;
    public ?string $senderName = null;
    public ?string $senderAvatar = null;
    public ?string $channelId = null;
    public ?string $timestamp = null;

    public function __construct(
        string $title = '',
        string $body = '',
        string $type = 'system',
        ?string $referenceId = null,
        ?string $imageUrl = null,
        ?string $clickAction = null,
        string $sound = 'default',
        string $priority = 'high',
        array $customData = [],
        bool $isDataOnly = false,
        ?string $entityType = null,
        ?string $entityId = null,
        ?string $action = null,
        ?string $senderId = null,
        ?string $senderName = null,
        ?string $senderAvatar = null,
        ?string $channelId = null,
        ?string $timestamp = null
    ) {
        $this->title = $title;
        $this->body = $body;
        $this->type = $type;
        $this->referenceId = $referenceId ? (string) $referenceId : null;
        $this->imageUrl = $imageUrl;
        $this->clickAction = $clickAction;
        $this->sound = $sound;
        $this->priority = $priority;
        $this->customData = $customData;
        $this->isDataOnly = $isDataOnly;

        $this->entityType = $entityType ?: $type;
        $this->entityId = $entityId ?: ($referenceId ? (string) $referenceId : null);
        $this->action = $action ?: 'NAVIGATE';
        $this->senderId = $senderId ? (string) $senderId : null;
        $this->senderName = $senderName;
        $this->senderAvatar = $senderAvatar;
        $this->channelId = $channelId;
        $this->timestamp = $timestamp ?: now()->toIso8601String();
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
            // Canonical Enterprise Contract
            'entity_type'   => 'call',
            'entity_id'     => (string) $sessionId,
            'action'        => 'RING',
            'sender_id'     => (string) $callerId,
            'sender_name'   => $callerName,
            'sender_avatar' => $callerAvatar ?? '',

            // Legacy Compatibility Aliases (Preserves Existing Flutter Parsers)
            'type'          => 'call',
            'session_id'    => (string) $sessionId,
            'caller_id'     => (string) $callerId,
            'caller_name'   => $callerName,
            'caller_avatar' => $callerAvatar ?? '',
            'call_type'     => $callType,
            'created_at'    => now()->toIso8601String(),
        ], $extra);

        return new self(
            title: "Incoming {$callType} call",
            body: "{$callerName} is calling you...",
            type: 'call',
            referenceId: (string) $sessionId,
            imageUrl: $callerAvatar,
            clickAction: null,
            sound: 'call_ringtone',
            priority: 'high',
            customData: $data,
            isDataOnly: true,
            entityType: 'call',
            entityId: (string) $sessionId,
            action: 'RING',
            senderId: (string) $callerId,
            senderName: $callerName,
            senderAvatar: $callerAvatar,
            channelId: 'call_channel'
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
            // Canonical Enterprise Contract
            'entity_type'   => 'chat',
            'entity_id'     => (string) $sessionId,
            'action'        => 'OPEN_CHAT',
            'sender_id'     => (string) $senderId,
            'sender_name'   => $senderName,
            'sender_avatar' => $senderAvatar ?? '',

            // Legacy Compatibility Aliases
            'type'          => 'chat',
            'session_id'    => (string) $sessionId,
            'sender_id'     => (string) $senderId,
            'sender_name'   => $senderName,
            'click_action'  => 'FLUTTER_NOTIFICATION_CLICK',
            'created_at'    => now()->toIso8601String(),
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
            isDataOnly: false,
            entityType: 'chat',
            entityId: (string) $sessionId,
            action: 'OPEN_CHAT',
            senderId: (string) $senderId,
            senderName: $senderName,
            senderAvatar: $senderAvatar,
            channelId: 'chat_channel'
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
            // Canonical Enterprise Contract
            'entity_type'     => $channelType,
            'entity_id'       => (string) $sessionId,
            'action'          => 'SESSION_REQUEST',
            'sender_id'       => (string) $userId,
            'sender_name'     => $userName,
            'sender_avatar'   => $userAvatar ?? '',

            // Legacy Compatibility Aliases
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
            isDataOnly: true,
            entityType: $channelType,
            entityId: (string) $sessionId,
            action: 'SESSION_REQUEST',
            senderId: (string) $userId,
            senderName: $userName,
            senderAvatar: $userAvatar,
            channelId: ($channelType === 'call' ? 'call_channel' : 'chat_channel')
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

        $data = array_merge([
            // Canonical Enterprise Contract
            'entity_type'     => $channelType,
            'entity_id'       => (string) $sessionId,
            'action'          => 'SESSION_ACCEPTED',
            'sender_id'       => (string) $astrologerId,
            'sender_name'     => $astrologerName,
            'sender_avatar'   => $astrologerAvatar ?? '',

            // Legacy Compatibility Aliases
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
            isDataOnly: false,
            entityType: $channelType,
            entityId: (string) $sessionId,
            action: 'SESSION_ACCEPTED',
            senderId: (string) $astrologerId,
            senderName: $astrologerName,
            senderAvatar: $astrologerAvatar,
            channelId: ($channelType === 'call' ? 'call_channel' : 'chat_channel')
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
            // Canonical Enterprise Contract
            'entity_type'    => $channelType,
            'entity_id'      => (string) $sessionId,
            'action'         => 'SESSION_ENDED',
            'recipient_role' => $recipientRole,

            // Legacy Compatibility Aliases
            'type'           => $typeStr,
            'session_id'     => (string) $sessionId,
            'channel_type'   => $channelType,
            'screen_route'   => '/session-summary',
            'click_action'   => 'FLUTTER_NOTIFICATION_CLICK',
            'created_at'     => now()->toIso8601String(),
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
            isDataOnly: false,
            entityType: $channelType,
            entityId: (string) $sessionId,
            action: 'SESSION_ENDED',
            channelId: ($channelType === 'call' ? 'call_channel' : 'chat_channel')
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
            // Canonical Enterprise Contract
            'entity_type'  => $type,
            'entity_id'    => $referenceId ? (string) $referenceId : '',
            'action'       => 'NAVIGATE',

            // Legacy Compatibility Aliases
            'type'         => $type,
            'reference_id' => $referenceId ? (string) $referenceId : '',
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'created_at'   => now()->toIso8601String(),
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
            isDataOnly: false,
            entityType: $type,
            entityId: $referenceId ? (string) $referenceId : null,
            action: 'NAVIGATE'
        );
    }

    /**
     * Build a Live Session notification payload with full Flutter deep-linking data contract.
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
            'reminder'  => "Live Session Starting Soon! ⏰",
            default     => "{$astrologerName} is Live Now! 🔴",
        };

        $defaultBody = match ($status) {
            'scheduled' => "{$astrologerName} has scheduled a live session. Don't miss it!",
            'reminder'  => "{$astrologerName} is going live in a few minutes. Get ready!",
            default     => "Join the live session now to interact directly and ask your questions.",
        };

        $data = array_merge([
            // Canonical Enterprise Contract
            'entity_type'       => 'live_stream',
            'entity_id'         => (string) $sessionId,
            'action'            => 'OPEN_LIVE_ROOM',
            'sender_id'         => (string) $astrologerId,
            'sender_name'       => $astrologerName,
            'sender_avatar'     => $astrologerAvatar ?? '',

            // Legacy Compatibility Aliases
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
            isDataOnly: false,
            entityType: 'live_stream',
            entityId: (string) $sessionId,
            action: 'OPEN_LIVE_ROOM',
            senderId: (string) $astrologerId,
            senderName: $astrologerName,
            senderAvatar: $astrologerAvatar,
            channelId: 'live_session_channel'
        );
    }

    /**
     * Strictly sanitize and format the data map for Google FCM v1.
     * Google FCM HTTP v1 mandates that all keys and values in the 'data' block MUST be strings.
     */
    public function getSanitizedData(bool $isSoundEnabled = true, string $sound = 'default'): array
    {
        $dataMap = [];

        // 1. Process custom data ensuring strict string typing
        foreach ($this->customData as $k => $v) {
            if (is_array($v) || is_object($v)) {
                $dataMap[(string) $k] = json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } elseif (is_bool($v)) {
                $dataMap[(string) $k] = $v ? '1' : '0';
            } elseif (is_null($v)) {
                $dataMap[(string) $k] = '';
            } else {
                $dataMap[(string) $k] = (string) $v;
            }
        }

        // 2. Attach canonical enterprise contract keys
        $dataMap['entity_type']  = (string) ($this->entityType ?: $this->type);
        $dataMap['entity_id']    = (string) ($this->entityId ?: $this->referenceId ?: '');
        $dataMap['action']       = (string) ($this->action ?: 'NAVIGATE');
        if ($this->clickAction) {
            $dataMap['click_action'] = (string) $this->clickAction;
        }
        $dataMap['timestamp']    = (string) ($this->timestamp ?: now()->toIso8601String());

        if ($this->senderId && !isset($dataMap['sender_id'])) {
            $dataMap['sender_id'] = (string) $this->senderId;
        }
        if ($this->senderName && !isset($dataMap['sender_name'])) {
            $dataMap['sender_name'] = (string) $this->senderName;
        }
        if ($this->senderAvatar && !isset($dataMap['sender_avatar'])) {
            $dataMap['sender_avatar'] = (string) $this->senderAvatar;
        }

        // 3. Attach standard notification control flags
        $dataMap['type']       = (string) $this->type;
        $dataMap['title']      = (string) $this->title;
        $dataMap['body']       = (string) $this->body;
        $dataMap['play_sound'] = $isSoundEnabled ? '1' : '0';
        $dataMap['sound']      = $isSoundEnabled ? (string) $sound : '';

        if ($this->referenceId && !isset($dataMap['reference_id'])) {
            $dataMap['reference_id'] = (string) $this->referenceId;
        }
        if ($this->imageUrl && !isset($dataMap['image'])) {
            $dataMap['image'] = (string) $this->imageUrl;
        }

        return $dataMap;
    }

    /**
     * Convert to standard array for storing in database logs.
     */
    public function toArray(): array
    {
        return [
            'title'         => $this->title,
            'body'          => $this->body,
            'type'          => $this->type,
            'entity_type'   => $this->entityType,
            'entity_id'     => $this->entityId,
            'action'        => $this->action,
            'reference_id'  => $this->referenceId,
            'image_url'     => $this->imageUrl,
            'click_action'  => $this->clickAction,
            'sound'         => $this->sound,
            'priority'      => $this->priority,
            'custom_data'   => $this->customData,
            'is_data_only'  => $this->isDataOnly,
            'channel_id'    => $this->channelId,
        ];
    }
}
