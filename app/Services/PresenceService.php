<?php

namespace App\Services;

use App\Repositories\UserRepository;

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

    public function setOffline($userId)
    {
        // Automatically cancel/reject any initiated chats where this user is a participant
        $initiatedSession = \App\Models\ChatSession::where('status', 'initiated')
            ->where(function ($query) use ($userId) {
                $query->where('consumer_id', $userId)
                      ->orWhere('provider_id', $userId);
            })
            ->first();

        if ($initiatedSession) {
            try {
                // Mark session as rejected/cancelled
                \App\Models\ChatSession::where('id', $initiatedSession->id)->update([
                    'status' => 'rejected',
                    'ended_at' => now(),
                ]);

                // Reset presence: free up the other user and mark the offline user as offline
                $consumerId = $initiatedSession->consumer_id;
                $providerId = $initiatedSession->provider_id;

                if ($userId == $consumerId) {
                    $this->userRepo->updatePresence($consumerId, false, false, null);
                    $this->userRepo->updatePresence($providerId, true, false, null);
                } else {
                    $this->userRepo->updatePresence($providerId, false, false, null);
                    $this->userRepo->updatePresence($consumerId, true, false, null);
                }

                // Broadcast ChatDismissed to notify the other participant
                broadcast(new \App\Events\ChatDismissed($initiatedSession->refresh(), $userId));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Auto-cancel on offline failed: " . $e->getMessage());
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
     * Handle automated chat cancellation when a member disconnects/leaves presence-room channel.
     */
    public function handleMemberLeft($event)
    {
        $userId = $event->user->id;

        $initiatedSession = \App\Models\ChatSession::where('status', 'initiated')
            ->where(function ($query) use ($userId) {
                $query->where('consumer_id', $userId)
                      ->orWhere('provider_id', $userId);
            })
            ->first();

        if ($initiatedSession) {
            try {
                // Mark session as rejected/cancelled
                \App\Models\ChatSession::where('id', $initiatedSession->id)->update([
                    'status' => 'rejected',
                    'ended_at' => now(),
                ]);

                // Reset presence for both users
                $this->userRepo->updatePresence($initiatedSession->consumer_id, false, false, null);
                $this->userRepo->updatePresence($initiatedSession->provider_id, true, false, null);

                // Broadcast ChatDismissed to notify the other participant
                broadcast(new \App\Events\ChatDismissed($initiatedSession->refresh(), $userId));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Presence event auto-cancel failed: " . $e->getMessage());
            }
        }
    }
}
