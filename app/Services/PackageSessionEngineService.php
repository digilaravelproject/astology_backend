<?php

namespace App\Services;

use App\Events\CallDismissed;
use App\Events\CallEnded;
use App\Events\CallInitiated;
use App\Events\ChatAccepted;
use App\Events\ChatDismissed;
use App\Events\ChatEnded;
use App\Events\ChatInitiated;
use App\Events\ChatQueueUpdated;
use App\Events\PackageSessionStateUpdated;
use App\Events\PackageSessionTerminated;
use App\Helpers\MediaHelper;
use App\Models\CallSession;
use App\Models\ChatSession;
use App\Models\PackagePurchase;
use App\Models\PackageSubSession;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PackageSessionEngineService
 * 
 * Orchestrates multi-channel prepaid package sessions:
 * - Subchannel spawning (Call/Chat)
 * - Zero-deduction Channel Switching (Call <-> Chat)
 * - Channel & Complete Session Termination
 * - Heartbeat Tracking & Inactivity Watchdog
 */
class PackageSessionEngineService
{
    public function __construct(
        protected ChatService $chatService,
        protected CallService $callService,
        protected PresenceService $presenceService
    ) {}

    // =========================================================================
    // 1. DURATION & CALCULATION UTILITIES
    // =========================================================================

    /**
     * Get real-time remaining seconds for an active or paused sub-session.
     */
    public function getRemainingSeconds(PackageSubSession $subSession, ?PackagePurchase $purchase = null): int
    {
        $purchase = $purchase ?? $subSession->purchase;
        if (!$purchase) {
            return 0;
        }

        if ($subSession->session_state === 'terminated' || !is_null($subSession->ended_at)) {
            return (int) $purchase->remaining_duration;
        }

        if (is_null($subSession->started_at)) {
            return (int) $purchase->remaining_duration;
        }

        $now = now();
        $totalElapsed = (int) $subSession->started_at->diffInSeconds($now);

        // Account for previous pause durations
        $activeElapsed = max(0, $totalElapsed - (int) $subSession->pause_duration_seconds);

        // If currently paused, subtract time spent in current pause window
        if ($subSession->session_state === 'paused' && $subSession->paused_at) {
            $currentPause = (int) $subSession->paused_at->diffInSeconds($now);
            $activeElapsed = max(0, $activeElapsed - $currentPause);
        }

        $remaining = (int) ($purchase->remaining_duration - $activeElapsed);
        return max(0, $remaining);
    }

    // =========================================================================
    // 2. CHANNEL MANAGEMENT (SPAWN, SWITCH, TERMINATE)
    // =========================================================================

    /**
     * Spawn an additional subchannel (Call or Chat) inside an active package session.
     */
    public function spawnSubchannel(int $subSessionId, string $channelType, int $actorId, array $options = []): array
    {
        return DB::transaction(function () use ($subSessionId, $channelType, $actorId, $options) {
            $subSession = PackageSubSession::with(['purchase.user', 'purchase.astrologer.astrologer'])
                ->lockForUpdate()
                ->findOrFail($subSessionId);
            $purchase = $subSession->purchase;

            if ($purchase->user_id !== $actorId && $purchase->astrologer_id !== $actorId) {
                throw new Exception("Unauthorized to modify this package session.", 403);
            }

            if ($subSession->session_state === 'terminated' || $purchase->status === 'exhausted') {
                throw new Exception("This package session is already ended or exhausted.", 422);
            }

            // Ensure session timer is running
            if (is_null($subSession->started_at)) {
                $subSession->started_at = now();
                $subSession->session_state = 'in_progress';
            }

            $userId = $purchase->user_id;
            $astrologerId = $purchase->astrologer_id;
            $user = $purchase->user;

            if ($channelType === 'call') {
                if (!$subSession->call_session_id || in_array($subSession->call_status, ['idle', 'disconnected', 'none'])) {
                    $linkedCall = $this->callService->initiateCall($userId, $astrologerId, true);
                    $subSession->call_session_id = $linkedCall->id;
                    $subSession->call_status = 'ringing';

                    if ($user) {
                        broadcast(new CallInitiated($linkedCall, [
                            'id'             => $user->id,
                            'name'           => $user->name,
                            'profile_photo'  => MediaHelper::getFullUrl($user->profile_photo),
                            'offer'          => $options['offer'] ?? 'audio',
                            'is_package'     => true,
                            'sub_session_id' => $subSession->id,
                        ]));
                    }
                }
            } elseif ($channelType === 'chat') {
                if (!$subSession->chat_session_id || in_array($subSession->chat_status, ['idle', 'closed', 'none'])) {
                    $question = $options['question'] ?? null;
                    $linkedChat = $this->chatService->initiateChat($userId, $astrologerId, $question, true);
                    $subSession->chat_session_id = $linkedChat->id;
                    $subSession->chat_status = 'active';

                    if ($user) {
                        broadcast(new ChatInitiated($linkedChat, $user));
                        broadcast(new ChatQueueUpdated($linkedChat->provider_id, $linkedChat, 'initiated'));
                    }
                } else {
                    $subSession->chat_status = 'active';
                }
            }

            $subSession->save();

            $remaining = $this->getRemainingSeconds($subSession, $purchase);
            $bannerData = $subSession->toBannerArray($remaining);

            broadcast(new PackageSessionStateUpdated($bannerData, $userId, $astrologerId));

            return [
                'sub_session'        => $subSession->fresh(),
                'banner_data'        => $bannerData,
                'remaining_seconds'  => $remaining,
            ];
        });
    }

    /**
     * Atomically switch from one subchannel to another (e.g. Chat -> Call or Call -> Chat).
     * Guaranteed 100% free of charge and zero wallet deduction under database row locks.
     */
    public function switchChannel(int $subSessionId, string $fromChannel, string $toChannel, int $actorId, array $options = []): array
    {
        return DB::transaction(function () use ($subSessionId, $fromChannel, $toChannel, $actorId, $options) {
            $subSession = PackageSubSession::with(['purchase.user', 'purchase.astrologer.astrologer'])
                ->lockForUpdate()
                ->findOrFail($subSessionId);
            $purchase = $subSession->purchase;

            if ($purchase->user_id !== $actorId && $purchase->astrologer_id !== $actorId) {
                throw new Exception("Unauthorized to modify this package session.", 403);
            }

            if ($subSession->session_state === 'terminated' || $purchase->status === 'exhausted') {
                throw new Exception("This package session is already ended or exhausted.", 422);
            }

            $userId = $purchase->user_id;
            $astrologerId = $purchase->astrologer_id;
            $user = $purchase->user;
            $now = now();

            // 1. Gracefully close the previous channel with zero cost ($0.00)
            if ($fromChannel === 'call' && $subSession->call_session_id) {
                $subSession->call_status = 'disconnected';
                $call = CallSession::find($subSession->call_session_id);
                if ($call && $call->status !== 'completed') {
                    $call->update(['status' => 'completed', 'ended_at' => $now, 'rate_per_minute' => 0.00, 'total_cost' => 0.00]);
                    broadcast(new CallEnded($call, $userId));
                }
            } elseif ($fromChannel === 'chat' && $subSession->chat_session_id) {
                $subSession->chat_status = 'closed';
                $chat = ChatSession::find($subSession->chat_session_id);
                if ($chat && !in_array($chat->status, ['completed', 'cancelled', 'rejected', 'timeout'])) {
                    $chat->update(['status' => 'completed', 'ended_at' => $now, 'rate_per_minute' => 0.00, 'total_cost' => 0.00]);
                    broadcast(new ChatEnded($chat, $userId));
                }
            }

            // 2. Ensure timer is initialized
            if (is_null($subSession->started_at)) {
                $subSession->started_at = now();
                $subSession->session_state = 'in_progress';
            }

            // 3. Initiate or reactivate the target channel
            $newSessionData = [];
            if ($toChannel === 'call') {
                if (!$subSession->call_session_id || in_array($subSession->call_status, ['idle', 'disconnected', 'none'])) {
                    $linkedCall = $this->callService->initiateCall($userId, $astrologerId, true);
                    $subSession->call_session_id = $linkedCall->id;
                    $subSession->call_status = 'ringing';
                    $newSessionData['call_session'] = $linkedCall;

                    if ($user) {
                        broadcast(new CallInitiated($linkedCall, [
                            'id'             => $user->id,
                            'name'           => $user->name,
                            'profile_photo'  => MediaHelper::getFullUrl($user->profile_photo),
                            'offer'          => $options['offer'] ?? 'audio',
                            'is_package'     => true,
                            'sub_session_id' => $subSession->id,
                        ]));
                    }
                }
                $subSession->mode = 'call';
            } elseif ($toChannel === 'chat') {
                if (!$subSession->chat_session_id || in_array($subSession->chat_status, ['idle', 'closed', 'none'])) {
                    $question = $options['question'] ?? null;
                    $linkedChat = $this->chatService->initiateChat($userId, $astrologerId, $question, true);

                    // Auto-activate chat session immediately
                    $linkedChat->update([
                        'status'      => 'ongoing',
                        'started_at'  => $subSession->started_at ?? now(),
                        'accepted_at' => now(),
                    ]);

                    $subSession->chat_session_id = $linkedChat->id;
                    $subSession->chat_status = 'active';
                    $newSessionData['chat_session'] = $linkedChat->fresh();
                    $newSessionData['chat_session_id'] = $linkedChat->id;

                    $linkedChat->load(['provider.astrologer', 'consumer']);
                    broadcast(new ChatAccepted($linkedChat, $linkedChat->provider));
                    if ($user) {
                        broadcast(new ChatInitiated($linkedChat, $user));
                        broadcast(new ChatQueueUpdated($linkedChat->provider_id, $linkedChat, 'ongoing'));
                    }
                } else {
                    $subSession->chat_status = 'active';
                    $chat = ChatSession::find($subSession->chat_session_id);
                    if ($chat) {
                        if ($chat->status !== 'ongoing' && $chat->status !== 'accepted') {
                            $chat->update(['status' => 'ongoing', 'ended_at' => null]);
                        }
                        $newSessionData['chat_session'] = $chat;
                        $newSessionData['chat_session_id'] = $chat->id;
                    }
                }
                $subSession->mode = 'chat';
            }

            $subSession->save();

            $remaining = $this->getRemainingSeconds($subSession, $purchase);
            $bannerData = $subSession->toBannerArray($remaining);

            broadcast(new PackageSessionStateUpdated($bannerData, $userId, $astrologerId));

            return array_merge([
                'action_performed'   => 'switch_channel',
                'from_channel'       => $fromChannel,
                'to_channel'         => $toChannel,
                'sub_session_id'     => $subSession->id,
                'chat_session_id'    => $subSession->chat_session_id,
                'call_session_id'    => $subSession->call_session_id,
                'sub_session'        => $subSession->fresh(),
                'banner_data'        => $bannerData,
                'remaining_seconds'  => $remaining,
            ], $newSessionData);
        });
    }

    /**
     * Terminate either a single subchannel (End Call Only) OR the complete session.
     */
    public function terminateSubchannel(int $subSessionId, string $channelType, string $action, int $actorId): array
    {
        return DB::transaction(function () use ($subSessionId, $channelType, $action, $actorId) {
            $subSession = PackageSubSession::with(['purchase.user', 'purchase.astrologer.astrologer'])
                ->lockForUpdate()
                ->findOrFail($subSessionId);
            $purchase = $subSession->purchase;

            if ($purchase->user_id !== $actorId && $purchase->astrologer_id !== $actorId) {
                throw new Exception("Unauthorized to modify this package session.", 403);
            }

            $userId = $purchase->user_id;
            $astrologerId = $purchase->astrologer_id;
            $now = now();

            if (in_array($action, ['end_channel_only', 'channel_only'])) {
                if ($channelType === 'call') {
                    $subSession->call_status = 'disconnected';
                    if ($subSession->call_session_id) {
                        $call = CallSession::find($subSession->call_session_id);
                        if ($call) {
                            $this->closeCallSession($call, $userId, $now);
                        }
                    }
                } elseif ($channelType === 'chat') {
                    $subSession->chat_status = 'closed';
                    if ($subSession->chat_session_id) {
                        $chat = ChatSession::find($subSession->chat_session_id);
                        if ($chat) {
                            $this->closeChatSession($chat, $userId, $now);
                        }
                    }
                }

                // If both channels are now inactive, auto-terminate complete session
                if (in_array($subSession->call_status, ['disconnected', 'idle', 'none']) &&
                    in_array($subSession->chat_status, ['closed', 'idle', 'none'])) {
                    return $this->finalizeCompleteSession($subSession, $purchase, $actorId);
                }

                $subSession->save();
                $remaining = $this->getRemainingSeconds($subSession, $purchase);
                $bannerData = $subSession->toBannerArray($remaining);

                broadcast(new PackageSessionStateUpdated($bannerData, $userId, $astrologerId));

                return [
                    'action_performed'   => 'end_channel_only',
                    'sub_session'        => $subSession->fresh(),
                    'banner_data'        => $bannerData,
                    'remaining_seconds'  => $remaining,
                ];
            }

            // Option 2: Complete Session Termination
            return $this->finalizeCompleteSession($subSession, $purchase, $actorId);
        });
    }

    // =========================================================================
    // 3. COMPLETE SESSION FINALIZATION & TEARDOWN
    // =========================================================================

    /**
     * Finalize and close the entire package session.
     */
    protected function finalizeCompleteSession(PackageSubSession $subSession, PackagePurchase $purchase, ?int $actorId = null): array
    {
        $now = now();
        $actorId = $actorId ?? $purchase->user_id;

        // 1. Close linked call if active
        if ($subSession->call_session_id) {
            $call = CallSession::find($subSession->call_session_id);
            if ($call) {
                $this->closeCallSession($call, $actorId, $now);
            }
        }

        // 2. Close linked chat if active
        if ($subSession->chat_session_id) {
            $chat = ChatSession::find($subSession->chat_session_id);
            if ($chat) {
                $this->closeChatSession($chat, $actorId, $now);
            }
        }

        // 3. Clean up and broadcast termination to any lingering sessions between these two users
        $this->cleanAndBroadcastLingeringSessions($purchase->user_id, $purchase->astrologer_id, $actorId, $now);

        // 4. Compute accurate atomic duration used (1x rate)
        $totalElapsed = $subSession->started_at ? (int) $subSession->started_at->diffInSeconds($now) : 0;
        $activeDuration = max(0, $totalElapsed - (int) $subSession->pause_duration_seconds);
        $durationDeducted = (int) min($activeDuration, $purchase->remaining_duration);

        $subSession->ended_at = $now;
        $subSession->duration_used = $durationDeducted;
        $subSession->session_state = 'terminated';
        $subSession->call_status = 'disconnected';
        $subSession->chat_status = 'closed';
        $subSession->save();

        // 5. Update PackagePurchase balance
        $purchase->remaining_duration = max(0, (int) ($purchase->remaining_duration - $durationDeducted));
        if ($purchase->remaining_duration <= 0) {
            $purchase->status = 'exhausted';
        }
        $purchase->save();

        // 6. Free presence and flush catalog cache
        $this->cleanupPresenceAndCache($purchase->user_id, $purchase->astrologer_id);

        $bannerData = $subSession->toBannerArray($purchase->remaining_duration);

        if ($purchase->status === 'exhausted' || $purchase->remaining_duration <= 0) {
            broadcast(new PackageSessionTerminated(
                $purchase,
                'Your package session has exhausted all remaining balance.',
                $subSession->mode ?? 'chat'
            ));
        }
        broadcast(new PackageSessionStateUpdated($bannerData, $purchase->user_id, $purchase->astrologer_id));

        return [
            'action_performed'   => 'end_complete_session',
            'sub_session'        => $subSession->fresh(),
            'banner_data'        => $bannerData,
            'duration_used'      => $durationDeducted,
            'remaining_seconds'  => $purchase->remaining_duration,
        ];
    }

    // =========================================================================
    // 4. HEARTBEAT & INACTIVITY WATCHDOG
    // =========================================================================

    /**
     * Record client heartbeat ping from User or Astrologer.
     */
    public function recordHeartbeat(int $subSessionId, int $actorId): array
    {
        $subSession = PackageSubSession::with('purchase')->findOrFail($subSessionId);
        $purchase = $subSession->purchase;

        if ($purchase->user_id === $actorId) {
            $subSession->update(['last_heartbeat_user' => now()]);
        } elseif ($purchase->astrologer_id === $actorId) {
            $subSession->update(['last_heartbeat_astrologer' => now()]);
        }

        $remaining = $this->getRemainingSeconds($subSession, $purchase);

        return [
            'status'            => 'success',
            'remaining_seconds' => $remaining,
            'session_state'     => $subSession->session_state,
        ];
    }

    /**
     * Inactivity Watchdog: Auto-pause abandoned sessions.
     */
    public function checkInactivityAndAutoPause(): int
    {
        $threshold = now()->subSeconds(90);

        $activeSessions = PackageSubSession::with(['purchase.user', 'purchase.astrologer'])
            ->where('session_state', 'in_progress')
            ->whereNotNull('started_at')
            ->whereNull('ended_at')
            ->where(function ($query) use ($threshold) {
                $query->where(function ($subQ) use ($threshold) {
                    $subQ->whereNull('last_heartbeat_user')
                         ->where('started_at', '<', $threshold);
                })->orWhere('last_heartbeat_user', '<', $threshold);
            })
            ->where(function ($query) use ($threshold) {
                $query->where(function ($subQ) use ($threshold) {
                    $subQ->whereNull('last_heartbeat_astrologer')
                         ->where('started_at', '<', $threshold);
                })->orWhere('last_heartbeat_astrologer', '<', $threshold);
            })
            ->get();

        $pausedCount = 0;
        foreach ($activeSessions as $session) {
            try {
                $session->update([
                    'session_state' => 'paused',
                    'paused_at'     => now(),
                ]);

                $purchase = $session->purchase;
                $remaining = $this->getRemainingSeconds($session, $purchase);
                $bannerData = $session->toBannerArray($remaining);

                broadcast(new PackageSessionStateUpdated($bannerData, $purchase->user_id, $purchase->astrologer_id));

                NotificationHelper::send(
                    $purchase->user_id,
                    "Session Paused ⏸️",
                    "Your session was automatically paused to protect your remaining package minutes.",
                    ['type' => 'package_session', 'sub_session_id' => $session->id]
                );

                NotificationHelper::send(
                    $purchase->astrologer_id,
                    "Session Paused ⏸️",
                    "The session with {$purchase->user?->name} was paused due to inactivity.",
                    ['type' => 'package_session', 'sub_session_id' => $session->id]
                );

                $pausedCount++;
            } catch (Exception $e) {
                Log::error("Failed to auto-pause inactive session #{$session->id}: " . $e->getMessage());
            }
        }

        return $pausedCount;
    }

    // =========================================================================
    // 5. INTERNAL REUSABLE TEARDOWN HELPERS
    // =========================================================================

    /**
     * Close a call session and broadcast the appropriate event.
     */
    protected function closeCallSession(CallSession $call, int $actorId, Carbon $endedAt): void
    {
        if (in_array($call->status, ['completed', 'missed', 'rejected'])) {
            return;
        }

        $wasRinging = in_array($call->status, ['initiated', 'ringing']);
        $call->update([
            'status'   => $wasRinging ? 'missed' : 'completed',
            'ended_at' => $endedAt,
        ]);

        if ($wasRinging) {
            broadcast(new CallDismissed($call, $actorId, 'cancelled'));
        } else {
            broadcast(new CallEnded($call, $actorId));
        }
    }

    /**
     * Close a chat session and broadcast the appropriate event.
     */
    protected function closeChatSession(ChatSession $chat, int $actorId, Carbon $endedAt): void
    {
        if (in_array($chat->status, ['completed', 'cancelled', 'rejected', 'timeout'])) {
            return;
        }

        $wasInitiated = in_array($chat->status, ['initiated', 'waiting']);
        $chat->update([
            'status'   => $wasInitiated ? 'cancelled' : 'completed',
            'ended_at' => $endedAt,
        ]);

        if ($wasInitiated) {
            broadcast(new ChatDismissed($chat, $actorId, 'cancelled'));
        } else {
            broadcast(new ChatEnded($chat, $actorId));
        }
    }

    /**
     * Find, close and broadcast termination events to any lingering sessions between two users.
     */
    protected function cleanAndBroadcastLingeringSessions(int $userId, int $astrologerId, int $actorId, Carbon $endedAt): void
    {
        $lingeringCalls = CallSession::where('consumer_id', $userId)
            ->where('provider_id', $astrologerId)
            ->whereIn('status', ['initiated', 'ringing', 'waiting', 'accepted', 'ongoing', 'active'])
            ->get();

        foreach ($lingeringCalls as $call) {
            $this->closeCallSession($call, $actorId, $endedAt);
        }

        $lingeringChats = ChatSession::where('consumer_id', $userId)
            ->where('provider_id', $astrologerId)
            ->whereIn('status', ['initiated', 'waiting', 'accepted', 'ongoing', 'active'])
            ->get();

        foreach ($lingeringChats as $chat) {
            $this->closeChatSession($chat, $actorId, $endedAt);
        }
    }

    /**
     * Reset presence and flush astrologer catalog cache.
     */
    protected function cleanupPresenceAndCache(int $userId, int $astrologerId): void
    {
        $this->presenceService->setFree($userId);
        $this->presenceService->setFree($astrologerId);

        User::whereIn('id', [$userId, $astrologerId])
            ->update(['is_busy' => false, 'busy_session_id' => null]);

        AstrologerService::flushCatalogCache();
    }
}
