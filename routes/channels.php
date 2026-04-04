<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function ($user, $id) {
    // Debug: Log the incoming ID and authenticated user ID
    \Illuminate\Support\Facades\Log::info("Broadcast Auth: Channel ID=$id, User ID=" . ($user ? $user->id : 'NULL'));
    return $user && (int) $user->id === (int) $id;
});

Broadcast::channel('presence-room', function ($user) {
    return ['id' => $user->id, 'name' => $user->name, 'profile_photo' => $user->profile_photo];
});
