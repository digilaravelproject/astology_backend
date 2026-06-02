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
        return $this->userRepo->updatePresence($userId, true, false, null);
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
                } else {
                    $this->userRepo->updatePresence($providerId, false, false, null);
                    $this->userRepo->updatePresence($consumerId, true, false, null);
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
                } else {
                    $this->userRepo->updatePresence($providerId, false, false, null);
                    $this->userRepo->updatePresence($consumerId, true, false, null);
                }

                // CallDismissed notifies both parties so their ring UI is dismissed
                broadcast(new \App\Events\CallDismissed($initiatedCall->refresh(), $userId, 'cancelled'));
            } catch (\Exception $e) {
                Log::error("Auto-cancel call on offline failed: " . $e->getMessage());
            }
        }

        return $this->userRepo->updatePresence($userId, false, false, null);
    }

    public function setBusy($userId, $sessionId)
    {
        return $this->userRepo->updatePresence($userId, true, true, $sessionId);
    }

    public function setFree($userId)
    {
        return $this->userRepo->updatePresence($userId, true, false, null);
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
