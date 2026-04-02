<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function findById($id)
    {
        return User::with('wallet')->find($id);
    }
    
    public function updatePresence($id, $isOnline, $isBusy = false, $sessionId = null)
    {
        return User::where('id', $id)->update([
            'is_online' => $isOnline,
            'is_busy' => $isBusy,
            'busy_session_id' => $sessionId,
            'last_seen_at' => now(),
        ]);
    }
}
