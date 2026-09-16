<?php

namespace App\Services;

use App\Models\ChatAssistanceSession;
use App\Models\ChatAssistanceMessage;
use App\Models\ChatAssistanceAstrologerLimit;
use App\Models\ChatAssistanceEvent;
use App\Models\Setting;
use App\Models\User;
use App\Models\CallSession;
use App\Models\ChatSession;
use App\Models\UserBlock;
use App\Events\ChatAssistanceInitiated;
use App\Events\MessageSent;
use App\Events\MessageStatusUpdated;
use App\Services\Notification\PushNotificationPayload;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use App\Helpers\MediaHelper;
use App\Services\ContentSanitizerService;

class ChatAssistanceService
{
    /**
     * Verify if a user is an existing client of the astrologer (has past consultation history).
     * Rule: Only users with prior ChatSession or CallSession can access Assistant Chat.
     * Also checks block status.
     */
    public function isExistingUser(int $consumerId, int $providerId): bool
    {
        // 1. Check if either party has blocked the other
        $isBlocked = UserBlock::where(function ($query) use ($consumerId, $providerId) {
            $query->where('blocker_id', $consumerId)->where('blocked_id', $providerId);
        })->orWhere(function ($query) use ($consumerId, $providerId) {
            $query->where('blocker_id', $providerId)->where('blocked_id', $consumerId);
        })->exists();

        if ($isBlocked) {
            return false;
        }

        // 2. Check past chat session history
        $hasChatHistory = ChatSession::where('consumer_id', $consumerId)
            ->where('provider_id', $providerId)
            ->exists();

        if ($hasChatHistory) {
            return true;
        }

        // 3. Check past call session history
        $hasCallHistory = CallSession::where('consumer_id', $consumerId)
            ->where('provider_id', $providerId)
            ->exists();

        return $hasCallHistory;
    }

    /**
     * Initiate or retrieve a Chat Assistance session.
     */
    public function initiateChat($consumerId, $providerId, $callSessionId = null)
    {
        if (!Setting::get('chat_assistance_enabled', true)) {
            throw new Exception("Chat Assistance feature is currently disabled by Admin.");
        }

        // Determine canonical consumer & provider IDs
        $user1 = User::find($consumerId);
        $user2 = User::find($providerId);

        $finalConsumerId = $consumerId;
        $finalProviderId = $providerId;

        if ($user1 && $user2) {
            if ($user1->user_type === 'astrologer' && $user2->user_type === 'user') {
                $finalConsumerId = $providerId;
                $finalProviderId = $consumerId;
            }
        }

        // Strict Rule: Consumer must be an existing user with prior consultation history
        if (!$this->isExistingUser($finalConsumerId, $finalProviderId)) {
            throw new Exception("You can only connect with this astrologer's assistant after having at least one consultation with the astrologer.", 403);
        }

        return DB::transaction(function () use ($finalConsumerId, $finalProviderId, $consumerId, $callSessionId) {
            $session = ChatAssistanceSession::where('consumer_id', $finalConsumerId)
                ->where('provider_id', $finalProviderId)
                ->lockForUpdate()
                ->first();

            if (!$session) {
                try {
                    $session = ChatAssistanceSession::create([
                        'consumer_id' => $finalConsumerId,
                        'provider_id' => $finalProviderId,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    $session = ChatAssistanceSession::where('consumer_id', $finalConsumerId)
                        ->where('provider_id', $finalProviderId)
                        ->lockForUpdate()
                        ->first();

                    if (!$session) {
                        throw $e;
                    }
                }
            }

            // Track session initiation event
            $this->logEvent($session->id, 'chat_initiated', [
                'initiated_by' => $consumerId,
                'call_session_id' => $callSessionId
            ]);

            if ($callSessionId) {
                $this->logEvent($session->id, 'chat_opened_during_call', [
                    'call_session_id' => $callSessionId
                ]);
            }

            return $session;
        });
    }

    /**
     * Send a message within a Chat Assistance session.
     */
    public function sendMessage($sessionId, $senderId, array $data)
    {
        if (!Setting::get('chat_assistance_enabled', true)) {
            throw new Exception("Chat Assistance feature is currently disabled by Admin.");
        }

        $session = ChatAssistanceSession::findOrFail($sessionId);

        // Security: Sender must be part of the session
        if ($session->consumer_id != $senderId && $session->provider_id != $senderId) {
            throw new Exception("Unauthorized participation in this chat assistance session.", 403);
        }

        $receiverId = ($session->consumer_id == $senderId) ? $session->provider_id : $session->consumer_id;

        // Active Call correlation: check if user is currently on an active call
        $callSessionId = $data['call_session_id'] ?? null;
        if (!$callSessionId) {
            $activeCall = CallSession::where(function ($query) use ($session) {
                $query->where('consumer_id', $session->consumer_id)
                      ->where('provider_id', $session->provider_id);
            })
            ->whereIn('status', ['ringing', 'accepted', 'ongoing'])
            ->latest()
            ->first();
            
            if ($activeCall) {
                $callSessionId = $activeCall->id;
            }
        }

        $sender = User::findOrFail($senderId);
        $isAstrologer = ($sender->user_type === 'astrologer');

        // Strict Rule: If consumer is sending, verify existing user qualification
        if (!$isAstrologer && !$this->isExistingUser($session->consumer_id, $session->provider_id)) {
            throw new Exception("You can only connect with this astrologer's assistant after having at least one consultation with the astrologer.", 403);
        }

        $message = DB::transaction(function () use ($session, $senderId, $receiverId, $isAstrologer, $data, $callSessionId) {
            if ($isAstrologer) {
                // Astrologer outgoing reply check
                $limitConfig = (int) Setting::get('chat_assistance_daily_limit', 5);
                $today = Carbon::today()->toDateString();

                $limitRecord = ChatAssistanceAstrologerLimit::where('astrologer_id', $senderId)
                    ->where('date', $today)
                    ->lockForUpdate()
                    ->first();

                if (!$limitRecord) {
                    $limitRecord = ChatAssistanceAstrologerLimit::create([
                        'astrologer_id' => $senderId,
                        'date' => $today,
                        'reply_count' => 0
                    ]);
                    $limitRecord = ChatAssistanceAstrologerLimit::where('id', $limitRecord->id)
                        ->lockForUpdate()
                        ->first();
                }

                if ($limitRecord->reply_count >= $limitConfig) {
                    // Log limit reached event
                    $this->logEvent($session->id, 'reply_limit_reached', [
                        'astrologer_id' => $senderId,
                        'limit_configured' => $limitConfig
                    ]);

                    throw new Exception("Daily message reply limit reached. You cannot send more replies today.");
                }

                $limitRecord->increment('reply_count');
            }

            // Create Message with sanitized content
            $rawMsg = $data['message'] ?? null;
            $sanitizedMsg = $rawMsg ? ContentSanitizerService::sanitize($rawMsg) : null;

            $message = ChatAssistanceMessage::create([
                'chat_assistance_session_id' => $session->id,
                'reply_to_id' => $data['reply_to_id'] ?? null,
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'message' => $sanitizedMsg,
                'attachment_url' => $data['attachment_url'] ?? null,
                'type' => $data['type'] ?? 'text',
                'call_session_id' => $callSessionId,
                'is_read' => false,
                'is_delivered' => false,
            ]);

            // Update session timestamp
            $session->touch();

            // Log corresponding event
            $eventName = ($message->type === 'image') ? 'image_shared' : 'message_sent';
            $eventMetadata = ['message_id' => $message->id];
            if ($callSessionId) {
                $eventName .= '_during_call';
                $eventMetadata['call_session_id'] = $callSessionId;
            }
            $this->logEvent($session->id, $eventName, $eventMetadata);

            return $message;
        });

        if ($message->reply_to_id) {
            $message->load('replyTo');
        }
        $message->load('sender');

        // Broadcast real-time message OUTSIDE transaction using unified MessageSent event
        event(new MessageSent($message, $receiverId));

        // ROLE-BASED NOTIFICATION DISPATCHING & ANTI-SPAM THROTTLING
        try {
            $this->dispatchChatAssistanceNotification($session, $message, $sender, $receiverId);
        } catch (\Exception $e) {
            Log::error("ChatAssistanceService: Failed to dispatch push notification: " . $e->getMessage());
        }

        return $message;
    }

    /**
     * Dispatch role-based push notifications for Assistant Chat with anti-spam throttling.
     * Rule:
     * 1. User sends message -> Notify Astrologer only (deep link to thread).
     * 2. Assistant/Astrologer sends message -> Notify User only with "{Astrologer Name} (Assistant)" branding.
     * 3. Zero alerts to Astrologer on outbound assistant replies.
     * 4. 15-second throttle to avoid spam bursts.
     */
    protected function dispatchChatAssistanceNotification(ChatAssistanceSession $session, ChatAssistanceMessage $message, User $sender, int $receiverId): void
    {
        // Check 15-second throttle window to prevent sound pop-up spam
        $throttleKey = "assistant_chat_throttle_{$receiverId}_{$session->id}";
        if (Cache::has($throttleKey)) {
            Log::info("ChatAssistanceService: Push notification throttled (within 15s window) for receiver #{$receiverId} on Session #{$session->id}.");
            return;
        }

        $isAstrologer = ($sender->user_type === 'astrologer');
        $previewText = $message->message ?: ($message->type === 'image' ? 'Sent an image attachment' : 'Sent an attachment');
        $senderAvatar = $sender->profile_photo ? MediaHelper::getUrl($sender->profile_photo) : null;

        if ($isAstrologer) {
            // Direction: Assistant -> User
            // Target recipient is User only. Astrologer receives ZERO notifications.
            $payload = PushNotificationPayload::forChatAssistance(
                sessionId: $session->id,
                senderId: $sender->id,
                senderName: $sender->name,
                messagePreview: $previewText,
                direction: 'assistant_to_user',
                senderAvatar: $senderAvatar,
                astrologerName: $sender->name
            );

            NotificationService::sendToUser($receiverId, $payload, saveInApp: true);
            Cache::put($throttleKey, true, now()->addSeconds(15));
            Log::info("ChatAssistanceService: Dispatched assistant reply push notification to User #{$receiverId}.");
        } else {
            // Direction: User -> Astrologer
            // Target recipient is Astrologer
            // Retrieve astrologer details
            $provider = User::find($receiverId);
            $astrologerName = $provider?->name ?? 'Astrologer';

            $payload = PushNotificationPayload::forChatAssistance(
                sessionId: $session->id,
                senderId: $sender->id,
                senderName: $sender->name,
                messagePreview: $previewText,
                direction: 'user_to_astrologer',
                senderAvatar: $senderAvatar,
                astrologerName: $astrologerName
            );

            NotificationService::sendToUser($receiverId, $payload, saveInApp: true);
            Cache::put($throttleKey, true, now()->addSeconds(15));
            Log::info("ChatAssistanceService: Dispatched user query push notification to Astrologer #{$receiverId}.");
        }
    }

    /**
     * Retrieve messages history with pagination.
     * In mobile chat apps, Page 1 MUST return the MOST RECENT messages (today's chat)
     * ordered chronologically for rendering.
     */
    public function getMessagesForSession($sessionId, $userId, $perPage = 50, $direction = 'asc')
    {
        $session = ChatAssistanceSession::findOrFail($sessionId);

        if ($session->consumer_id != $userId && $session->provider_id != $userId) {
            throw new Exception("Unauthorized access to this chat history.", 403);
        }

        // Fetch latest messages first so Page 1 contains the most recent messages (e.g. today's chat)
        $paginator = ChatAssistanceMessage::with(['replyTo', 'sender'])
            ->where('chat_assistance_session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        // Reverse for display so Page 1 renders chronologically (oldest -> newest within the slice)
        $items = ($direction === 'desc')
            ? $paginator->getCollection()->values()
            : $paginator->getCollection()->reverse()->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            [
                'path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Get astrologer daily replies count & remaining status.
     */
    public function getAstrologerLimitStatus($astrologerId)
    {
        $limitConfig = (int) Setting::get('chat_assistance_daily_limit', 5);
        $today = Carbon::today()->toDateString();

        $limitRecord = ChatAssistanceAstrologerLimit::where('astrologer_id', $astrologerId)
            ->where('date', $today)
            ->first();

        $used = $limitRecord ? (int) $limitRecord->reply_count : 0;
        $remaining = max(0, $limitConfig - $used);

        return [
            'limit' => $limitConfig,
            'used' => $used,
            'remaining' => $remaining,
        ];
    }

    /**
     * Mark message status as read/seen or delivered.
     */
    public function syncMessageStatus($sessionId, $userId, $status, array $messageIds)
    {
        $session = ChatAssistanceSession::findOrFail($sessionId);

        if ($session->consumer_id != $userId && $session->provider_id != $userId) {
            throw new Exception("Unauthorized access.", 403);
        }

        $query = ChatAssistanceMessage::where('chat_assistance_session_id', $sessionId)
            ->whereIn('id', $messageIds)
            ->where('receiver_id', $userId);

        $syncedAt = now();

        if ($status === 'delivered') {
            $query->update(['is_delivered' => true]);
            $eventName = 'message_delivered';
        } elseif ($status === 'seen') {
            $query->update(['is_read' => true, 'is_delivered' => true]);
            $eventName = 'message_read';
        } else {
            throw new Exception("Invalid status parameter.");
        }

        // Log events for the messages
        foreach ($messageIds as $msgId) {
            $this->logEvent($session->id, $eventName, ['message_id' => $msgId]);
        }

        $senderToNotify = ($session->consumer_id == $userId) ? $session->provider_id : $session->consumer_id;

        broadcast(new MessageStatusUpdated(
            $messageIds,
            $status,
            $senderToNotify,
            (int) $sessionId,
            $userId,
            $syncedAt->toIso8601String()
        ));
    }

    /**
     * Log a chat assistance event.
     */
    public function logEvent($sessionId, $eventName, $metadata = null)
    {
        try {
            ChatAssistanceEvent::create([
                'chat_assistance_session_id' => $sessionId,
                'event_name' => $eventName,
                'metadata' => $metadata,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to log chat assistance event: " . $e->getMessage());
        }
    }

    /**
     * Get chat assistance sessions for a user (either consumer or provider).
     */
    public function getSessions($userId, $perPage = 15)
    {
        $sessions = ChatAssistanceSession::with([
                'consumer:id,name,profile_photo',
                'provider:id,name,profile_photo',
                'provider.astrologer',
                'latestMessage'
            ])
            ->withCount(['messages as unread_count' => function ($query) use ($userId) {
                $query->where('receiver_id', $userId)->where('is_read', false);
            }])
            ->where(function ($query) use ($userId) {
                $query->where('consumer_id', $userId)
                      ->orWhere('provider_id', $userId);
            })
            ->latest('updated_at')
            ->paginate($perPage);

        $sessions->getCollection()->transform(function ($session) use ($userId) {
            $session->chat_assistance_session_id = $session->id;
            if ($session->consumer) {
                $session->consumer->profile_photo = MediaHelper::getFullUrl($session->consumer->profile_photo);
            }
            if ($session->provider) {
                $session->provider->profile_photo = MediaHelper::getFullUrl($session->provider->profile_photo);
                
                $completedChats = \App\Models\ChatSession::where('provider_id', $session->provider_id)->where('status', 'completed')->count();
                $completedCalls = \App\Models\CallSession::where('provider_id', $session->provider_id)->where('status', 'completed')->count();
                $totalOrders = 120 + $completedChats + $completedCalls;

                $session->provider->total_orders = $totalOrders;
                $session->provider->orders_count = $totalOrders;
                $session->provider->orders_formatted = "{$totalOrders}+ orders";
            }

            // Provide aliases for maximum mobile client compatibility
            $session->astrologer = $session->provider;
            $session->user = $session->consumer;
            $session->other_user = ($session->consumer_id == $userId) ? $session->provider : $session->consumer;

            return $session;
        });

        return $sessions;
    }
}
