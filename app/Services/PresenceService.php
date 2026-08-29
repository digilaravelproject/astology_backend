<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;
use Throwable;

class PresenceService
{
    protected $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    /**
     * Mark a user online, detect active session engagement, and broadcast availability.
     */
    public function setOnline($userId)
    {
        try {
            $user = \App\Models\User::find($userId);
            if (!$user) {
                Log::warning("PresenceService::setOnline - User #{$userId} not found");
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
        } catch (Throwable $e) {
            Log::error("PresenceService::setOnline failed for user #{$userId}: " . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Mark a user offline and broadcast updated availability.
     */
    public function setOffline($userId)
    {
        try {
            $result = $this->userRepo->updatePresence($userId, false, false, null);
            $this->broadcastAstrologerAvailability($userId, false, false, null);
            return $result;
        } catch (Throwable $e) {
            Log::error("PresenceService::setOffline failed for user #{$userId}: " . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Mark an astrologer busy during an ongoing session.
     */
    public function setBusy($userId, $sessionId, ?string $sessionType = null)
    {
        try {
            $result = $this->userRepo->updatePresence($userId, true, true, $sessionId);
            $this->broadcastAstrologerAvailability($userId, true, true, $sessionId, $sessionType);
            return $result;
        } catch (Throwable $e) {
            Log::error("PresenceService::setBusy failed for user #{$userId}: " . $e->getMessage(), [
                'session_id' => $sessionId,
                'session_type' => $sessionType,
                'exception' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Mark an astrologer free after session completion.
     */
    public function setFree($userId)
    {
        try {
            $astro = \App\Models\Astrologer::where('user_id', $userId)->first();
            $isOnline = $astro ? (bool) ($astro->is_online || $astro->is_chat_enabled || $astro->is_call_enabled || $astro->is_video_call_enabled) : true;

            $result = $this->userRepo->updatePresence($userId, $isOnline, false, null);
            $this->broadcastAstrologerAvailability($userId, $isOnline, false, null);
            return $result;
        } catch (Throwable $e) {
            Log::error("PresenceService::setFree failed for user #{$userId}: " . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
            ]);
            return false;
        }
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

                // Invalidate astrologers catalog cache so next request gets updated online list instantly
                \App\Services\AstrologerService::flushCatalogCache();
            }
        } catch (Throwable $e) {
            Log::warning("Broadcasting AstrologerAvailabilityUpdated failed for user #{$userId}: " . $e->getMessage());
        }
    }

    /**
     * Handle automated presence telemetry when a member disconnects/leaves presence-room channel.
     * Safely updates presence status without mutating active transactional call/chat sessions.
     */
    public function handleMemberLeft($event)
    {
        try {
            $userId = $event->user->id ?? null;
            if (!$userId) {
                return;
            }

            Log::info("Presence member left event received for user #{$userId}");
            $this->setOffline($userId);
        } catch (Throwable $e) {
            Log::error("PresenceService::handleMemberLeft exception: " . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
            ]);
        }
    }
}
