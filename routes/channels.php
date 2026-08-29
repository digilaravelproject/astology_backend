<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\CallSession;
use App\Models\ChatSession;
use App\Models\ChatAssistanceSession;
use App\Models\LiveSession;

/*
|--------------------------------------------------------------------------
| Broadcast Channel Authorization Definitions
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

/*
|--------------------------------------------------------------------------
| 1. Direct User Channel (Signaling & Push Events)
|--------------------------------------------------------------------------
| Used for 1-on-1 signaling events (CallInitiated, ChatInitiated, MessageSent, etc.).
| Only the authenticated user whose ID matches the channel suffix may subscribe.
*/
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
}, ['guards' => ['sanctum']]);

/*
|--------------------------------------------------------------------------
| 2. WebRTC Call Session Channel
|--------------------------------------------------------------------------
| Used for mid-call signaling (ICE candidates, SDP re-exchange, call end).
| Only the EXACT two participants (consumer_id OR provider_id) of the given
| call session may subscribe. Any 3rd party is instantly rejected.
*/
Broadcast::channel('call.{sessionId}', function ($user, $sessionId) {
    $session = CallSession::find((int) $sessionId);

    if (!$session) {
        return false;
    }

    return (int) $user->id === (int) $session->consumer_id
        || (int) $user->id === (int) $session->provider_id;
}, ['guards' => ['sanctum']]);

/*
|--------------------------------------------------------------------------
| 3. 1-on-1 Chat Consultation Room Channel
|--------------------------------------------------------------------------
| Room channel for real-time chat consultations (Standard & Prepaid Packages).
| Both consumer and provider subscribe to receive sanitized messages and status events.
*/
Broadcast::channel('chat.{sessionId}', function ($user, $sessionId) {
    $session = ChatSession::find((int) $sessionId);

    if (!$session) {
        return false;
    }

    return (int) $user->id === (int) $session->consumer_id
        || (int) $user->id === (int) $session->provider_id;
}, ['guards' => ['sanctum']]);

/*
|--------------------------------------------------------------------------
| 4. Chat Assistance Session Room Channel
|--------------------------------------------------------------------------
| Room channel for free/assistance consultation chat sessions.
| Restricts access strictly to the participating consumer and astrologer.
*/
Broadcast::channel('chat-assistance.{sessionId}', function ($user, $sessionId) {
    $session = ChatAssistanceSession::find((int) $sessionId);

    if (!$session) {
        return false;
    }

    return (int) $user->id === (int) $session->consumer_id
        || (int) $user->id === (int) $session->provider_id;
}, ['guards' => ['sanctum']]);

Broadcast::channel('chat_assistance.{sessionId}', function ($user, $sessionId) {
    $session = ChatAssistanceSession::find((int) $sessionId);

    if (!$session) {
        return false;
    }

    return (int) $user->id === (int) $session->consumer_id
        || (int) $user->id === (int) $session->provider_id;
}, ['guards' => ['sanctum']]);

/*
|--------------------------------------------------------------------------
| 5. Live Stream Broadcasting Presence Channel
|--------------------------------------------------------------------------
| One-to-many live streaming: viewers subscribe to see comments, super chats,
| and live viewer count. Only authenticated users may join for active sessions.
*/
Broadcast::channel('live-session.{id}', function ($user, $id) {
    $session = LiveSession::find((int) $id);

    if (!$session || !in_array($session->status, ['ongoing', 'completed'])) {
        return false;
    }

    return [
        'id'            => $user->id,
        'name'          => $user->name,
        'profile_photo' => $user->profile_photo,
    ];
}, ['guards' => ['sanctum']]);

/*
|--------------------------------------------------------------------------
| 6. Global Room Presence Channel
|--------------------------------------------------------------------------
*/
Broadcast::channel('room', function ($user) {
    return [
        'id'            => $user->id,
        'name'          => $user->name,
        'profile_photo' => $user->profile_photo,
    ];
}, ['guards' => ['sanctum']]);
