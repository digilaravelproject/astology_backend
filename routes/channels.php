<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\CallSession;

/*
|--------------------------------------------------------------------------
| Direct User Channel
|--------------------------------------------------------------------------
| Used for 1-on-1 signaling events (CallInitiated, CallDismissed, etc.).
| Only the authenticated user whose ID matches the channel suffix may subscribe.
|--------------------------------------------------------------------------
*/
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
}, ['guards' => ['sanctum']]);

/*
|--------------------------------------------------------------------------
| Call Session Channel
|--------------------------------------------------------------------------
| Used for mid-call signaling (ICE candidates, SDP re-exchange, call end).
| Only the EXACT two participants (consumer_id OR provider_id) of the given
| call session may subscribe. Any 3rd party is instantly rejected.
| This prevents eavesdropping even if a session ID is leaked.
|--------------------------------------------------------------------------
*/
Broadcast::channel('call.{sessionId}', function ($user, $sessionId) {
    $session = CallSession::find((int) $sessionId);

    if (!$session) {
        return false;
    }

    // Mathematically verify: user must be EITHER the consumer OR the provider
    return (int) $user->id === (int) $session->consumer_id
        || (int) $user->id === (int) $session->provider_id;
}, ['guards' => ['sanctum']]);

Broadcast::channel('presence-room', function ($user) {
    return ['id' => $user->id, 'name' => $user->name, 'profile_photo' => $user->profile_photo];
}, ['guards' => ['sanctum']]);

/*
|--------------------------------------------------------------------------
| Live Session Presence Channel
|--------------------------------------------------------------------------
| One-to-many live streaming: viewers subscribe to see comments, super chats,
| and live viewer count. Only authenticated users may join, and only for
| active (ongoing) sessions.
|--------------------------------------------------------------------------
*/
Broadcast::channel('live-session.{id}', function ($user, $id) {
    $session = \App\Models\LiveSession::find((int) $id);

    if (!$session || $session->status !== 'ongoing') {
        return false;
    }

    return [
        'id'            => $user->id,
        'name'          => $user->name,
        'profile_photo' => $user->profile_photo,
    ];
}, ['guards' => ['sanctum']]);
