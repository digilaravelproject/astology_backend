<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('presence-room', function ($user) {
    return ['id' => $user->id, 'name' => $user->name, 'profile_photo' => $user->profile_photo];
});
