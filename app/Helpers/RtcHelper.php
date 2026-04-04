<?php

namespace App\Helpers;

use App\Models\User;

class RtcHelper
{
    public static function formatUserPresence(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'profile_photo' => $user->profile_photo,
            'is_online' => $user->is_online,
            'is_busy' => $user->is_busy,
            'user_type' => $user->user_type,
            'last_seen_at' => $user->last_seen_at
        ];
    }
}
