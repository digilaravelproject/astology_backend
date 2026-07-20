<?php

namespace App\Services;

use App\Models\ChatAssistanceSession;
use App\Models\ChatAssistanceMessage;
use App\Models\ChatAssistanceAstrologerLimit;
use App\Models\ChatAssistanceEvent;
use App\Models\Setting;
use App\Models\User;
use App\Models\CallSession;
use App\Events\ChatAssistanceInitiated;
use App\Events\ChatAssistanceMessageSent;
use App\Events\ChatAssistanceMessageStatusUpdated;
use App\Events\ChatAssistanceLimitReached;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use App\Helpers\MediaHelper;

class ChatAssistanceService
{
    /**
     * Initiate or retrieve a Chat Assistance session.
     */
    public function initiateChat($consumerId, $providerId, $callSessionId = null)
    {
        if (!Setting::get('chat_assistance_enabled', true)) {
            throw new Exception("Chat Assistance feature is currently disabled by Admin.");
        }

        return DB::transaction(function () use ($consumerId, $providerId, $callSessionId) {
            $session = ChatAssistanceSession::where(function ($query) use ($consumerId, $providerId) {
                $query->where('consumer_id', $consumerId)
                      ->where('provider_id', $providerId);
            })->orWhere(function ($query) use ($consumerId, $providerId) {
                $query->where('consumer_id', $providerId)
                      ->where('provider_id', $consumerId);
            })->first();

            if (!$session) {
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

                $session = ChatAssistanceSession::create([
                    'consumer_id' => $finalConsumerId,
                    'provider_id' => $finalProviderId,
                ]);
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
        $isAstrologer = ($session->provider_id == $senderId);

        return DB::transaction(function () use ($session, $senderId, $receiverId, $isAstrologer, $data, $callSessionId) {
            if ($isAstrologer) {
                // Astrologer outgoing reply check
                $limitConfig = Setting::get('chat_assistance_daily_limit', 5);
                $today = Carbon::today();

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

                    broadcast(new ChatAssistanceLimitReached($senderId))->toOthers();

                    throw new Exception("Daily message reply limit reached. You cannot send more replies today.");
                }

                $limitRecord->increment('reply_count');
            }

            // Create Message
            $message = ChatAssistanceMessage::create([
                'chat_assistance_session_id' => $session->id,
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'message' => $data['message'] ?? null,
                'attachment_url' => $data['attachment_url'] ?? null,
                'type' => $data['type'] ?? 'text',
                'call_session_id' => $callSessionId,
                'is_read' => false,
                'is_delivered' => false,
            ]);

            // Log corresponding event
            $eventName = ($message->type === 'image') ? 'image_shared' : 'message_sent';
            $eventMetadata = ['message_id' => $message->id];
            if ($callSessionId) {
                $eventName .= '_during_call';
                $eventMetadata['call_session_id'] = $callSessionId;
            }
            $this->logEvent($session->id, $eventName, $eventMetadata);

            // Broadcast real-time message
            broadcast(new ChatAssistanceMessageSent($message, $receiverId));

            return $message;
        });
    }

    /**
     * Retrieve messages history (only from the last 3 days).
     */
    public function getMessagesForSession($sessionId, $userId)
    {
        $session = ChatAssistanceSession::findOrFail($sessionId);

        if ($session->consumer_id != $userId && $session->provider_id != $userId) {
            throw new Exception("Unauthorized access to this chat history.", 403);
        }

        // Only get messages from the last 3 days
        $threeDaysAgo = Carbon::now()->subDays(3);

        return ChatAssistanceMessage::where('chat_assistance_session_id', $sessionId)
            ->where('created_at', '>=', $threeDaysAgo)
            ->oldest()
            ->paginate(30);
    }

    /**
     * Get astrologer daily replies count & remaining status.
     */
    public function getAstrologerLimitStatus($astrologerId)
    {
        $limitConfig = Setting::get('chat_assistance_daily_limit', 5);
        $today = Carbon::today();

        $limitRecord = ChatAssistanceAstrologerLimit::where('astrologer_id', $astrologerId)
            ->where('date', $today)
            ->first();

        $used = $limitRecord ? $limitRecord->reply_count : 0;
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

        broadcast(new ChatAssistanceMessageStatusUpdated(
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
                'latestMessage'
            ])
            ->where('consumer_id', $userId)
            ->orWhere('provider_id', $userId)
            ->latest('updated_at')
            ->paginate($perPage);

        $sessions->getCollection()->transform(function ($session) {
            if ($session->consumer) {
                $session->consumer->profile_photo = MediaHelper::getFullUrl($session->consumer->profile_photo);
            }
            if ($session->provider) {
                $session->provider->profile_photo = MediaHelper::getFullUrl($session->provider->profile_photo);
            }
            return $session;
        });

        return $sessions;
    }
}
