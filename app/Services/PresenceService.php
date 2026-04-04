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
}
