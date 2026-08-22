<?php

namespace App\Services;

use App\Events\CallEnded;
use App\Events\CallInitiated;
use App\Events\ChatEnded;
use App\Events\ChatInitiated;
use App\Events\ChatQueueUpdated;
use App\Events\PackageSessionStateUpdated;
use App\Events\PackageSessionTerminated;
use App\Models\CallSession;
use App\Models\ChatSession;
use App\Models\PackagePurchase;
use App\Models\PackageSubSession;
use App\Models\User;
use App\Services\NotificationHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PackageSessionEngineService
{
    protected ChatService $chatService;
    protected CallService $callService;

    public function __construct(ChatService $chatService, CallService $callService)
    {
        $this->chatService = $chatService;
        $this->callService = $callService;
    }

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

        // If currently paused, subtract time spent in current pause
        if ($subSession->session_state === 'paused' && $subSession->paused_at) {
            $currentPause = (int) $subSession->paused_at->diffInSeconds($now);
            $activeElapsed = max(0, $activeElapsed - $currentPause);
        }

        $remaining = (int) ($purchase->remaining_duration - $activeElapsed);
        return max(0, $remaining);
    }

    /**
     * Spawn an additional subchannel (Call or Chat) inside an active package session.
     */
    public function spawnSubchannel(int $subSessionId, string $channelType, int $actorId, array $options = []): array
    {
        return DB::transaction(function () use ($subSessionId, $channelType, $actorId, $options) {
            $subSession = PackageSubSession::with(['purchase.user', 'purchase.astrologer.astrologer'])->lockForUpdate()->findOrFail($subSessionId);
            $purchase = $subSession->purchase;

            if ($purchase->user_id !== $actorId && $purchase->astrologer_id !== $actorId) {
                throw new Exception("Unauthorized to modify this package session.", 403);
            }

            if ($subSession->session_state === 'terminated' || $purchase->status === 'exhausted') {
                throw new Exception("This package session is already ended or exhausted.", 422);
            }

            // Ensure timer has started
            if (is_null($subSession->started_at)) {
                $subSession->started_at = now();
                $subSession->session_state = 'in_progress';
            }

            $userId = $purchase->user_id;
            $astrologerId = $purchase->astrologer_id;
            $user = $purchase->user;

            if ($channelType === 'call') {
                // If call is not already active, spawn WebRTC call session
                if (!$subSession->call_session_id || in_array($subSession->call_status, ['idle', 'disconnected', 'none'])) {
                    $linkedCall = $this->callService->initiateCall($userId, $astrologerId, true);
                    $subSession->call_session_id = $linkedCall->id;
                    $subSession->call_status = 'ringing';

                    if ($user) {
                        broadcast(new CallInitiated($linkedCall, [
                            'id'            => $user->id,
                            'name'          => $user->name,
                            'profile_photo' => \App\Helpers\MediaHelper::getFullUrl($user->profile_photo),
                            'offer'         => $options['offer'] ?? 'audio',
                            'is_package'    => true,
                            'sub_session_id'=> $subSession->id,
                        ]));
                    }
                }
            } elseif ($channelType === 'chat') {
                // If chat is not already active, spawn Chat session
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
            $subSession = PackageSubSession::with(['purchase.user', 'purchase.astrologer.astrologer'])->lockForUpdate()->findOrFail($subSessionId);
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

            // 1. If switching FROM call, disconnect the audio/video call gracefully ($0.00 charge)
            if ($fromChannel === 'call' && $subSession->call_session_id) {
                $subSession->call_status = 'disconnected';
                $call = CallSession::find($subSession->call_session_id);
                if ($call && $call->status !== 'completed') {
                    $call->update(['status' => 'completed', 'ended_at' => $now, 'rate_per_minute' => 0.00, 'total_cost' => 0.00]);
                    broadcast(new CallEnded($call, [
                        'is_package'     => true,
                        'sub_session_id' => $subSession->id,
                        'action'         => 'switch_channel',
                    ]));
                }
            }

            // 2. Ensure timer is initialized
            if (is_null($subSession->started_at)) {
                $subSession->started_at = now();
                $subSession->session_state = 'in_progress';
            }

            // 3. Initiate or preserve the target subchannel (Zero-Rate guaranteed)
            $newSessionData = [];
            if ($toChannel === 'call') {
                // If call is not already ongoing, initiate a zero-cost package call
                if (!$subSession->call_session_id || in_array($subSession->call_status, ['idle', 'disconnected', 'none'])) {
                    $linkedCall = $this->callService->initiateCall($userId, $astrologerId, true);
                    $subSession->call_session_id = $linkedCall->id;
                    $subSession->call_status = 'ringing';
                    $newSessionData['call_session'] = $linkedCall;

                    if ($user) {
                        broadcast(new CallInitiated($linkedCall, [
                            'id'            => $user->id,
                            'name'          => $user->name,
                            'profile_photo' => \App\Helpers\MediaHelper::getFullUrl($user->profile_photo),
                            'offer'         => $options['offer'] ?? 'audio',
                            'is_package'    => true,
                            'sub_session_id'=> $subSession->id,
                        ]));
                    }
                }
                // Keep chat active in background so user and astrologer can share charts/messages while on call
                $subSession->mode = 'call';
            } elseif ($toChannel === 'chat') {
                // If chat is not already active, initiate or reuse existing chat thread
                if (!$subSession->chat_session_id || in_array($subSession->chat_status, ['idle', 'closed', 'none'])) {
                    $question = $options['question'] ?? null;
                    $linkedChat = $this->chatService->initiateChat($userId, $astrologerId, $question, true);
                    
                    // Auto-activate chat session immediately (no redundant accept needed since already authenticated & in-call)
                    $linkedChat->update([
                        'status'      => 'ongoing',
                        'started_at'  => $subSession->started_at ?? now(),
                        'accepted_at' => now(),
                    ]);

                    $subSession->chat_session_id = $linkedChat->id;
                    $subSession->chat_status = 'active';
                    $newSessionData['chat_session'] = $linkedChat->fresh();
                    $newSessionData['chat_session_id'] = $linkedChat->id;

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
            $subSession = PackageSubSession::with(['purchase.user', 'purchase.astrologer.astrologer'])->lockForUpdate()->findOrFail($subSessionId);
            $purchase = $subSession->purchase;

            if ($purchase->user_id !== $actorId && $purchase->astrologer_id !== $actorId) {
                throw new Exception("Unauthorized to modify this package session.", 403);
            }

            $userId = $purchase->user_id;
            $astrologerId = $purchase->astrologer_id;
            $now = now();

            if ($action === 'end_channel_only') {
                // Option 1: "End Call Only" or "End Chat Only"
                if ($channelType === 'call') {
                    $subSession->call_status = 'disconnected';
                    if ($subSession->call_session_id) {
                        $call = CallSession::find($subSession->call_session_id);
                        if ($call && $call->status !== 'completed') {
                            $call->update(['status' => 'completed', 'ended_at' => $now]);
                            broadcast(new CallEnded($call, [
                                'is_package' => true,
                                'sub_session_id' => $subSession->id,
                                'action' => 'end_channel_only',
                            ]));
                        }
                    }
                } elseif ($channelType === 'chat') {
                    $subSession->chat_status = 'closed';
                    if ($subSession->chat_session_id) {
                        $chat = ChatSession::find($subSession->chat_session_id);
                        if ($chat && $chat->status !== 'completed') {
                            $chat->update(['status' => 'completed', 'ended_at' => $now]);
                            broadcast(new ChatEnded($chat, [
                                'is_package' => true,
                                'sub_session_id' => $subSession->id,
                                'action' => 'end_channel_only',
                            ]));
                        }
                    }
                }

                // If both channels are now inactive, auto-terminate complete session
                if (in_array($subSession->call_status, ['disconnected', 'idle', 'none']) &&
                    in_array($subSession->chat_status, ['closed', 'idle', 'none'])) {
                    return $this->finalizeCompleteSession($subSession, $purchase);
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

            // Option 2: "End Complete Session"
            return $this->finalizeCompleteSession($subSession, $purchase);
        });
    }

    /**
     * Finalize and close the entire package session.
     */
    protected function finalizeCompleteSession(PackageSubSession $subSession, PackagePurchase $purchase): array
    {
        $now = now();

        // 1. Close linked call if active
        if ($subSession->call_session_id) {
            $call = CallSession::find($subSession->call_session_id);
            if ($call && $call->status !== 'completed') {
                $call->update(['status' => 'completed', 'ended_at' => $now]);
                broadcast(new CallEnded($call, ['is_package' => true, 'sub_session_id' => $subSession->id]));
            }
        }

        // 2. Close linked chat if active
        if ($subSession->chat_session_id) {
            $chat = ChatSession::find($subSession->chat_session_id);
            if ($chat && $chat->status !== 'completed') {
                $chat->update(['status' => 'completed', 'ended_at' => $now]);
                broadcast(new ChatEnded($chat, ['is_package' => true, 'sub_session_id' => $subSession->id]));
            }
        }

        // 3. Compute accurate atomic duration used (1x rate)
        $totalElapsed = $subSession->started_at ? (int) $subSession->started_at->diffInSeconds($now) : 0;
        $activeDuration = max(0, $totalElapsed - (int) $subSession->pause_duration_seconds);
        $durationDeducted = (int) min($activeDuration, $purchase->remaining_duration);

        $subSession->ended_at = $now;
        $subSession->duration_used = $durationDeducted;
        $subSession->session_state = 'terminated';
        $subSession->call_status = 'disconnected';
        $subSession->chat_status = 'closed';
        $subSession->save();

        // 4. Update PackagePurchase balance
        $purchase->remaining_duration = max(0, (int) ($purchase->remaining_duration - $durationDeducted));
        if ($purchase->remaining_duration <= 0) {
            $purchase->status = 'exhausted';
        }
        $purchase->save();

        $bannerData = $subSession->toBannerArray($purchase->remaining_duration);

        broadcast(new PackageSessionTerminated($subSession, $purchase->remaining_duration));
        broadcast(new PackageSessionStateUpdated($bannerData, $purchase->user_id, $purchase->astrologer_id));

        return [
            'action_performed'   => 'end_complete_session',
            'sub_session'        => $subSession->fresh(),
            'banner_data'        => $bannerData,
            'duration_used'      => $durationDeducted,
            'remaining_seconds'  => $purchase->remaining_duration,
        ];
    }

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

                // Dispatch push notification alerts
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
}
