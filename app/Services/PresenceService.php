<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;

class PresenceService
{
    protected $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function setOnline($userId)
    {
        $user = \App\Models\User::find($userId);
        if (!$user) {
            return false;
        }

        // Check if user is currently engaged in an active chat or call session
        $activeChat = \App\Models\ChatSession::where(function ($query) use ($userId) {
                $query->where('consumer_id', $userId)
                      ->orWhere('provider_id', $userId);
            })
            ->whereIn('status', ['accepted', 'ongoing'])
            ->first();

        $activeCall = \App\Models\CallSession::where(function ($query) use ($userId) {
                $query->where('consumer_id', $userId)
                      ->orWhere('provider_id', $userId);
            })
            ->whereIn('status', ['ringing', 'accepted', 'ongoing'])
            ->first();

        $isBusy = ($activeChat || $activeCall || (bool) $user->is_busy);
        $sessionId = $activeChat ? $activeChat->id : ($activeCall ? $activeCall->id : $user->busy_session_id);
        $sessionType = $activeChat ? 'chat' : ($activeCall ? 'call' : null);

        $wasOnline = (bool) $user->is_online;
        $wasBusy = (bool) $user->is_busy;

        $result = $this->userRepo->updatePresence($userId, true, $isBusy, $sessionId);

        // Only broadcast if status changed (e.g. was offline or busy shifted)
        if (!$wasOnline || ($wasBusy !== $isBusy)) {
            $this->broadcastAstrologerAvailability($userId, true, $isBusy, $sessionId, $sessionType);
        }

        return $result;
    }

    /**
     * Mark a user offline. Auto-cancels any pending initiated chat/call sessions
     * so the other participant's ring screen is dismissed immediately.
     */
    public function setOffline($userId)
    {
        // ── Auto-cancel any initiated CHAT session ────────────────────────
        $initiatedChat = \App\Models\ChatSession::where('status', 'initiated')
            ->where(function ($query) use ($userId) {
                $query->where('consumer_id', $userId)
                      ->orWhere('provider_id', $userId);
            })
            ->first();

        if ($initiatedChat) {
            try {
                \App\Models\ChatSession::where('id', $initiatedChat->id)->update([
                    'status'   => 'rejected',
                    'ended_at' => now(),
                ]);

                $consumerId = $initiatedChat->consumer_id;
                $providerId = $initiatedChat->provider_id;

                if ($userId == $consumerId) {
                    $this->userRepo->updatePresence($consumerId, false, false, null);
                    $this->userRepo->updatePresence($providerId, true, false, null);
                    $this->broadcastAstrologerAvailability($providerId, true, false, null);
                } else {
                    $this->userRepo->updatePresence($providerId, false, false, null);
                    $this->userRepo->updatePresence($consumerId, true, false, null);
                    $this->broadcastAstrologerAvailability($providerId, false, false, null);
                }

                broadcast(new \App\Events\ChatDismissed($initiatedChat->refresh(), $userId));
            } catch (\Exception $e) {
                Log::error("Auto-cancel chat on offline failed: " . $e->getMessage());
            }
        }

        // ── Auto-cancel any initiated CALL session ────────────────────────
        $initiatedCall = \App\Models\CallSession::where('status', 'initiated')
            ->where(function ($query) use ($userId) {
                $query->where('consumer_id', $userId)
                      ->orWhere('provider_id', $userId);
            })
            ->first();

        if ($initiatedCall) {
            try {
                \App\Models\CallSession::where('id', $initiatedCall->id)->update([
                    'status'   => 'cancelled',
                    'ended_at' => now(),
                ]);

                $consumerId = $initiatedCall->consumer_id;
                $providerId = $initiatedCall->provider_id;

                if ($userId == $consumerId) {
                    $this->userRepo->updatePresence($consumerId, false, false, null);
                    $this->userRepo->updatePresence($providerId, true, false, null);
                    $this->broadcastAstrologerAvailability($providerId, true, false, null);
                } else {
                    $this->userRepo->updatePresence($providerId, false, false, null);
                    $this->userRepo->updatePresence($consumerId, true, false, null);
                    $this->broadcastAstrologerAvailability($providerId, false, false, null);
                }

                // CallDismissed notifies both parties so their ring UI is dismissed
                broadcast(new \App\Events\CallDismissed($initiatedCall->refresh(), $userId, 'cancelled'));
            } catch (\Exception $e) {
                Log::error("Auto-cancel call on offline failed: " . $e->getMessage());
            }
        }

        $result = $this->userRepo->updatePresence($userId, false, false, null);
        $this->broadcastAstrologerAvailability($userId, false, false, null);
        return $result;
    }

    public function setBusy($userId, $sessionId, ?string $sessionType = null)
    {
        $result = $this->userRepo->updatePresence($userId, true, true, $sessionId);
        $this->broadcastAstrologerAvailability($userId, true, true, $sessionId, $sessionType);
        return $result;
    }

    public function setFree($userId)
    {
        $astro = \App\Models\Astrologer::where('user_id', $userId)->first();
        $isOnline = $astro ? (bool) ($astro->is_online || $astro->is_chat_enabled || $astro->is_call_enabled || $astro->is_video_call_enabled) : true;

        $result = $this->userRepo->updatePresence($userId, $isOnline, false, null);
        $this->broadcastAstrologerAvailability($userId, $isOnline, false, null);
        return $result;
    }

    /**
     * Broadcast real-time availability update ("Engaged", "Online", "Offline") for astrologers.
     */
    public function broadcastAstrologerAvailability(int $userId, bool $isOnline, bool $isBusy, ?int $sessionId = null, ?string $sessionType = null): void
    {
        try {
            $astro = \App\Models\Astrologer::where('user_id', $userId)->first();
            if ($astro) {
                $isChatEnabled = (bool) ($astro->is_chat_enabled ?? $astro->chat_enabled ?? false);
                $isCallEnabled = (bool) ($astro->is_call_enabled ?? $astro->call_enabled ?? false);
                $isVideoCallEnabled = (bool) ($astro->is_video_call_enabled ?? $astro->video_call_enabled ?? false);

                broadcast(new \App\Events\AstrologerAvailabilityUpdated(
                    $userId,
                    $isOnline,
                    $isBusy,
                    $sessionId,
                    $sessionType,
                    $astro->id,
                    $isChatEnabled,
                    $isCallEnabled,
                    $isVideoCallEnabled
                ));
            }
        } catch (\Throwable $e) {
            Log::warning("Broadcasting AstrologerAvailabilityUpdated failed for user #{$userId}: " . $e->getMessage());
        }
    }

    /**
     * Handle automated cancellation when a member disconnects/leaves presence-room channel.
     * Covers both chat and call sessions.
     */
    public function handleMemberLeft($event)
    {
        $userId = $event->user->id;

        // ── Auto-cancel CHAT session ───────────────────────────────────────
        $initiatedChat = \App\Models\ChatSession::where('status', 'initiated')
            ->where(function ($query) use ($userId) {
                $query->where('consumer_id', $userId)
                      ->orWhere('provider_id', $userId);
            })
            ->first();

        if ($initiatedChat) {
            try {
                \App\Models\ChatSession::where('id', $initiatedChat->id)->update([
                    'status'   => 'rejected',
                    'ended_at' => now(),
                ]);

                $this->userRepo->updatePresence($initiatedChat->consumer_id, false, false, null);
                $this->userRepo->updatePresence($initiatedChat->provider_id, true, false, null);

                broadcast(new \App\Events\ChatDismissed($initiatedChat->refresh(), $userId));
            } catch (\Exception $e) {
                Log::error("Presence event chat auto-cancel failed: " . $e->getMessage());
            }
        }

        // ── Auto-cancel CALL session ───────────────────────────────────────
        $initiatedCall = \App\Models\CallSession::where('status', 'initiated')
            ->where(function ($query) use ($userId) {
                $query->where('consumer_id', $userId)
                      ->orWhere('provider_id', $userId);
            })
            ->first();

        if ($initiatedCall) {
            try {
                \App\Models\CallSession::where('id', $initiatedCall->id)->update([
                    'status'   => 'cancelled',
                    'ended_at' => now(),
                ]);

                $this->userRepo->updatePresence($initiatedCall->consumer_id, false, false, null);
                $this->userRepo->updatePresence($initiatedCall->provider_id, true, false, null);

                broadcast(new \App\Events\CallDismissed($initiatedCall->refresh(), $userId, 'cancelled'));
            } catch (\Exception $e) {
                Log::error("Presence event call auto-cancel failed: " . $e->getMessage());
            }
        }
    }
}
