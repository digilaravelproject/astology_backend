<?php

namespace App\Helpers;

use App\Models\User;

class RtcHelper
{
    public static function formatUserPresence(User $user): array
    {
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'profile_photo' => $user->profile_photo_url,
            'is_online' => $user->is_online,
            'is_busy' => $user->is_busy,
            'user_type' => $user->user_type,
            'last_seen_at' => $user->last_seen_at
        ];

        if ($user->user_type === 'astrologer' && $user->astrologer) {
            $data['is_chat_enabled'] = (bool) $user->astrologer->is_chat_enabled;
            $data['is_call_enabled'] = (bool) $user->astrologer->is_call_enabled;
        }

        return $data;
    }
}
