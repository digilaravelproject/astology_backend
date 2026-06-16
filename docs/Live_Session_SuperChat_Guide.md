# Live Session & Super Chat API & WebSocket Guide

Complete lifecycle documentation for Live Streaming and Super Chats. Includes all API routes (request/response) and Laravel Reverb WebSocket events for both **Scheduled** and **Instant** live streams.

---

## 1. Authentication & Base Setup

**Headers required for all API requests:**
```
Authorization: Bearer <sanctum_token>
Accept: application/json
Content-Type: application/json
```

**WebSocket broadcasting auth:**
```
POST /api/v1/broadcasting/auth
Headers: Authorization: Bearer <token>
```

---

## 2. Standard API Response Format

All API responses follow `App\Helpers\ApiResponse`.

**Success (2xx):**
```json
{
  "status": "success",
  "message": "Action completed successfully",
  "data": { ... }
}
```

**Error (4xx/5xx):**
```json
{
  "status": "error",
  "message": "Error description",
  "errors": { "field": ["validation message"] }
}
```

---

## 3. Live Session Model Fields

Common fields returned in all live session responses (`formatLiveSession()`):

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Session ID |
| `astrologer_id` | integer | Astrologer profile ID |
| `title` | string | Session title |
| `description` | string / null | Session description |
| `scheduled_at` | string (Y-m-d H:i:s) | Scheduled date/time |
| `scheduled_date` | string (Y-m-d) | Scheduled date only |
| `scheduled_time` | string (H:i:s) | Scheduled time only |
| `session_type` | string | `public` or `private` |
| `status` | string | `upcoming` / `ongoing` / `completed` / `cancelled` |
| `is_broadcasting` | boolean | LiveKit video broadcast active (true/false) |
| `duration_minutes` | integer | Duration in minutes |
| `max_participants` | integer | Max viewers (default: 100) |
| `current_participants` | integer | Current viewer count |
| `viewer_count` | integer | Counter (incremented on join) |
| `created_at` | string (Y-m-d H:i:s) | Record created |
| `updated_at` | string (Y-m-d H:i:s) | Record updated |

---

## 4. Astrologer Live Session API

Base: `/api/v1/astrologer/live` — all routes require `auth:sanctum` + `throttle:tiered`.

### 4.1 Create Scheduled Session

**POST** `/api/v1/astrologer/live`

**Request Body:**
```json
{
  "title": "Weekly Astrology Prediction",
  "description": "Weekly horoscope analysis",
  "scheduled_at": "2026-06-20 18:00:00",
  "session_type": "public",
  "duration_minutes": 60,
  "max_participants": 500
}
```

| Field | Required | Validation |
|-------|----------|------------|
| `title` | Yes | string, max:255 |
| `description` | No | string, max:1000 |
| `scheduled_at` | Yes | date_format:Y-m-d H:i:s, after:now |
| `session_type` | Yes | in:public,private |
| `duration_minutes` | No | integer, min:15, max:480, default:60 |
| `max_participants` | No | integer, min:1, max:5000, default:100 |

**Response (201 Created):**
```json
{
  "status": "success",
  "message": "Live session created successfully",
  "data": {
    "id": 15,
    "astrologer_id": 5,
    "title": "Weekly Astrology Prediction",
    "description": "Weekly horoscope analysis",
    "scheduled_at": "2026-06-20 18:00:00",
    "scheduled_date": "2026-06-20",
    "scheduled_time": "18:00:00",
    "session_type": "public",
    "status": "upcoming",
    "duration_minutes": 60,
    "max_participants": 500,
    "current_participants": 0,
    "viewer_count": 0,
    "created_at": "2026-06-16 14:00:00",
    "updated_at": "2026-06-16 14:00:00"
  }
}
```

---

### 4.2 Go Live Instantly

**POST** `/api/v1/astrologer/live` (same endpoint, different payload)

**Request Body:**
```json
{
  "title": "Instant Tarot Reading & QA",
  "description": "Ask me anything live!",
  "is_instant": true,
  "session_type": "public",
  "duration_minutes": 45,
  "max_participants": 300
}
```

| Field | Required | Validation |
|-------|----------|------------|
| `title` | Yes | string, max:255 |
| `description` | No | string, max:1000 |
| `is_instant` | Yes | boolean (`true` = instant live) |
| `session_type` | Yes | in:public,private |
| `duration_minutes` | No | integer, min:15, max:480, default:60 |
| `max_participants` | No | integer, min:1, max:5000, default:100 |

> When `is_instant=true`, `scheduled_at` is ignored (not required). Session is created with `status=ongoing` and `LiveSessionStarted` is broadcast immediately.

**Response (201 Created):**
```json
{
  "status": "success",
  "message": "Live session created successfully",
  "data": {
    "id": 16,
    "astrologer_id": 5,
    "title": "Instant Tarot Reading & QA",
    "description": "Ask me anything live!",
    "scheduled_at": "2026-06-16 14:30:00",
    "scheduled_date": "2026-06-16",
    "scheduled_time": "14:30:00",
    "session_type": "public",
    "status": "ongoing",
    "duration_minutes": 45,
    "max_participants": 300,
    "current_participants": 0,
    "viewer_count": 0,
    "created_at": "2026-06-16 14:30:00",
    "updated_at": "2026-06-16 14:30:00"
  }
}
```

**Real-time side-effects:**
- `LiveSessionStarted` broadcast on channel `live-sessions` (public)
- System push notification to ALL app users

---

### 4.3 List Live Sessions (Astrologer)

**GET** `/api/v1/astrologer/live?filter=upcoming|completed|all&per_page=15`

| Query | Required | Description |
|-------|----------|-------------|
| `filter` | No | `upcoming`, `completed`, or `all` (default: `all`) |
| `per_page` | No | Items per page (default: 15) |

**Response (200 OK) — with `filter=all`:**
```json
{
  "status": "success",
  "message": "Live sessions retrieved successfully",
  "data": {
    "upcoming": {
      "data": [
        {
          "id": 15,
          "title": "Weekly Astrology Prediction",
          "status": "upcoming",
          "scheduled_at": "2026-06-20 18:00:00",
          "viewer_count": 0
        }
      ],
      "total": 1
    },
    "completed": {
      "data": [
        {
          "id": 14,
          "title": "Past Session",
          "status": "completed"
        }
      ],
      "pagination": {
        "current_page": 1,
        "total_pages": 1,
        "per_page": 15,
        "total": 1
      }
    }
  }
}
```

**Response (200 OK) — with `filter=upcoming` or `filter=completed`:**
```json
{
  "status": "success",
  "message": "Live sessions retrieved successfully",
  "data": {
    "data": [
      { "id": 15, "title": "...", "status": "upcoming", ... }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "per_page": 15,
      "total": 5
    }
  }
}
```

---

### 4.4 Show Single Session (Astrologer)

**GET** `/api/v1/astrologer/live/{id}`

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Live session retrieved successfully",
  "data": { ... full session object ... }
}
```

**Error (404):**
```json
{
  "status": "error",
  "message": "Live session not found"
}
```

---

### 4.5 Update Session

**PUT** `/api/v1/astrologer/live/{id}`

Same fields as create — all optional (`sometimes` validation). Can also update `status`.

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Live session updated successfully",
  "data": { ... updated session ... }
}
```

> Cannot update `scheduled_at` for past sessions (422 error).

---

### 4.6 Delete Session

**DELETE** `/api/v1/astrologer/live/{id}`

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Live session 'Weekly Astrology Prediction' deleted successfully",
  "data": null
}
```

> Cannot delete `ongoing` sessions (422 error).

---

### 4.7 Start Scheduled Session (Go Live)

**POST** `/api/v1/astrologer/live/{id}/start`

No request body.

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Live session started successfully",
  "data": {
    "id": 15,
    "status": "ongoing",
    ...
  }
}
```

**Real-time side-effects:**
- `LiveSessionStarted` broadcast on channel `live-sessions` (public)
- System push notification to ALL app users

> Only `upcoming` sessions can be started (422 for `ongoing`/`completed`).

---

### 4.8 Stop Session (End Stream)

**POST** `/api/v1/astrologer/live/{id}/stop`

No request body.

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Live session ended successfully",
  "data": {
    "id": 15,
    "status": "completed",
    ...
  }
}
```

**Real-time side-effects:**
- `LiveSessionEnded` broadcast on channel `live-sessions` (public) + `live-session.{id}` (presence)
- LiveKit room is automatically deleted if one exists
- All viewers receive `LiveSessionEnded` event → close video player

> Only `ongoing` sessions can be stopped (422 for `upcoming`/`completed`).

---

### 4.9 Get Current Active Session

**GET** `/api/v1/astrologer/live/current`

Used to check if the astrologer has an ongoing live session.

**Response (200 OK) — Active stream found:**
```json
{
  "status": "success",
  "message": "Current active live session retrieved successfully",
  "data": { ... full session object ... }
}
```

**Response (200 OK) — No active stream:**
```json
{
  "status": "success",
  "message": "No active live session found",
  "data": null
}
```

---

### 4.10 Start LiveKit Broadcast (Go Live with Video)

**POST** `/api/v1/astrologer/live/{id}/broadcast`

Creates a LiveKit room and returns a **publisher token** so the astrologer app can publish video/audio.

No request body.

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Broadcast started successfully",
  "data": {
    "livekit_ws_url": "wss://live.suryapathkundli.com",
    "room_uuid": "live_12",
    "token": "eyJhbGciOiJIUzI1NiJ9..."
  }
}
```

| Field | Description |
|-------|-------------|
| `livekit_ws_url` | WebSocket URL for Flutter LiveKit client to connect |
| `room_uuid` | Unique LiveKit room name (e.g., `live_12`) |
| `token` | JWT publisher token (allows publishing video/audio) |

**Flutter Usage (Astrologer App — after receiving response):**

Add to `pubspec.yaml`:
```yaml
dependencies:
  livekit_client: ^2.0.0
  flutter_webrtc: ^1.0.0  # Required by livekit_client
  permission_handler: ^11.0.0
```

**Full broadcast function:**
```dart
import 'package:livekit_client/livekit_client.dart';
import 'package:permission_handler/permission_handler.dart';

class LiveBroadcastService {
  late Room _room;
  bool _isConnected = false;

  Future<void> startBroadcast(String wsUrl, String token) async {
    try {
      // 1. Request permissions
      await [
        Permission.camera,
        Permission.microphone,
      ].request();

      // 2. Create room + connect
      _room = Room();
      _room.on<RoomEvent>(onRoomEvent);
      await _room.connect(wsUrl, token);

      // 3. Publish camera
      LocalVideoTrack cameraTrack = await LocalVideoTrack.create();
      await _room.localParticipant?.publishTrack(cameraTrack);

      // 4. Publish mic
      LocalAudioTrack micTrack = await LocalAudioTrack.create();
      await _room.localParticipant?.publishTrack(micTrack);

      _isConnected = true;
      debugPrint('Broadcast started: ${_room.name}');
    } catch (e) {
      debugPrint('Broadcast failed: $e');
      rethrow;
    }
  }

  void onRoomEvent(RoomEvent event, dynamic data) {
    switch (event) {
      case RoomEvent.Disconnected:
        _isConnected = false;
        debugPrint('Disconnected from LiveKit');
        break;
      case RoomEvent.ParticipantConnected:
        debugPrint('Viewer joined: ${(data as RemoteParticipant).identity}');
        break;
      default:
        break;
    }
  }

  Future<void> stopBroadcast() async {
    if (_isConnected) {
      await _room.disconnect();
      _isConnected = false;
    }
  }

  void dispose() {
    _room.dispose();
  }
}
```

**Real-time side-effects:**
- `is_broadcasting` set to `true` on the session
- `AstrologerBroadcastStarted` event broadcast on `live-session.{id}` (presence)
- Users in the room receive the event → enable video player UI

**Error (422):**
```json
{
  "status": "error",
  "message": "Only ongoing sessions can start broadcasting"
}
```

> Only `ongoing` sessions can call this. If called again on an already broadcasting session, returns a fresh token.

---

### 4.11 Stop LiveKit Broadcast (Stop Video, Keep Chat)

**POST** `/api/v1/astrologer/live/{id}/stop-broadcast`

Stops the LiveKit video broadcast **without** ending the chat session. Chat, comments, and super chats continue working.

No request body.

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Broadcast stopped successfully",
  "data": null
}
```

**What happens:**
1. LiveKit room is deleted
2. `is_broadcasting` set to `false`
3. `room_uuid` cleared
4. All participants' `left_at` timestamps updated
5. Viewers' Flutter app gets disconnected from LiveKit (video stops, but chat stays)

> Useful when astrologer wants to stop video temporarily but keep the chat session alive.

---

## 5. User (Viewer) API

Base: `/api/v1/user/live` — all routes require `auth:sanctum` + `throttle:tiered`.

### 5.1 List Active Streams

**GET** `/api/v1/user/live/now`

No request body. Returns only public, ongoing sessions.

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Live sessions retrieved successfully",
  "data": [
    {
      "id": 16,
      "title": "Instant Tarot Reading & QA",
      "astrologer": {
        "id": 5,
        "name": "Priya Sharma",
        "profile_photo": "photos/abc.jpg"
      },
      "is_broadcasting": true,
      "viewer_count": 0
    }
  ]
}
```

> `is_broadcasting` indicates whether the astrologer has activated LiveKit video. If `false`, session is chat-only until the astrologer calls `broadcast()`.

---

### 5.2 Show Session Details (User)

**GET** `/api/v1/user/live/{id}`

Returns session with astrologer profile details (name, photo, gender, date_of_birth, skill).

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Live session retrieved successfully",
  "data": {
    "id": 16,
    "title": "Instant Tarot Reading & QA",
    "description": "Ask me anything live!",
    "session_type": "public",
    "status": "ongoing",
    "is_broadcasting": true,
    "viewer_count": 0,
    "astrologer": {
      "id": 5,
      "name": "Priya Sharma",
      "profile_photo": "photos/abc.jpg",
      "gender": "female",
      "date_of_birth": "1990-05-15"
    }
  }
}
```

---

### 5.3 Join Session

**POST** `/api/v1/user/live/{id}/join`

No request body. Increments `viewer_count`.

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Joined live session successfully",
  "data": null
}
```

**Real-time event:**
- `ViewerCountUpdated` on `live-session.{id}` (presence channel)

> After joining, call **`/watch`** (section 5.8) to get a LiveKit subscriber token for video playback.

> Error (400): `"Live session is not currently active"` if status is not `ongoing`.

---

### 5.4 Leave Session

**POST** `/api/v1/user/live/{id}/leave`

No request body. Decrements `viewer_count` and records `left_at` timestamp in `live_session_participants`.

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Left live session successfully",
  "data": null
}
```

**Real-time event:**
- `ViewerCountUpdated` on `live-session.{id}` (presence channel)

---

### 5.5 Send Comment

**POST** `/api/v1/user/live/{id}/comment`

**Request Body:**
```json
{
  "message": "Hello Priya, please answer my question!"
}
```

| Field | Required | Validation |
|-------|----------|------------|
| `message` | Yes | string, max:500 |

**Response (201 Created):**
```json
{
  "status": "success",
  "message": "Comment sent successfully",
  "data": {
    "id": 110,
    "message": "Hello Priya, please answer my question!",
    "created_at": "2026-06-16T14:32:00.000000Z"
  }
}
```

**Real-time event:**
- `NewLiveComment` on `live-session.{id}` (presence channel)

---

### 5.6 Send Super Chat (Gift Tip)

**POST** `/api/v1/user/live/{id}/super-chat`

**Request Body:**
```json
{
  "gift_id": 3,
  "message": "Here is a Red Rose for you!"
}
```

| Field | Required | Validation |
|-------|----------|------------|
| `gift_id` | Yes | integer, exists:gifts,id |
| `message` | No | string, max:500 |

**Response (201 Created):**
```json
{
  "status": "success",
  "message": "Super Chat sent successfully",
  "data": {
    "id": 22,
    "amount": 50.00,
    "message": "[Gift: Red Rose] Here is a Red Rose for you!",
    "created_at": "2026-06-16T14:34:00.000000Z"
  }
}
```

**Error (402 — Insufficient balance):**
```json
{
  "status": "error",
  "message": "Insufficient balance in your wallet."
}
```

**Error (422 — Gift inactive):**
```json
{
  "status": "error",
  "message": "Selected gift is not available."
}
```

**Transaction flow:**
1. Validate session is `ongoing` (400 if not)
2. Validate gift exists and `is_active = true`
3. Lock wallets in deterministic order (by user ID) to prevent deadlocks
4. Check wallet balance >= gift price (402 if insufficient)
5. Create `SuperChat` record with `transaction_status: pending`
6. Deduct from user wallet via `WalletService::deductForSuperChat()`
7. Credit astrologer wallet via `WalletService::creditAstrologerForSuperChat()`
8. Update `SuperChat` to `transaction_status: completed`
9. Broadcast `SuperChatReceived` event

**Real-time event:**
- `SuperChatReceived` on `live-session.{id}` (presence channel)

---

### 5.7 Get Comments (Paginated)

**GET** `/api/v1/user/live/{id}/comments?per_page=50`

| Query | Required | Description |
|-------|----------|-------------|
| `per_page` | No | Items per page (default: 50, max: 100) |

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Comments retrieved successfully",
  "data": {
    "data": [
      {
        "id": 110,
        "user_id": 42,
        "user_name": "Rahul Kumar",
        "user_avatar": "photos/user42.jpg",
        "message": "Hello Priya!",
        "created_at": "2026-06-16T14:32:00.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "per_page": 50,
      "total": 1
    }
  }
}
```

---

### 5.8 Get LiveKit Subscriber Token (Watch Video)

**POST** `/api/v1/user/live/{id}/watch`

Returns a LiveKit subscriber token. The Flutter app uses this to connect to LiveKit and receive the astrologer's video/audio stream.

No request body.

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Watch token generated successfully",
  "data": {
    "livekit_ws_url": "wss://live.suryapathkundli.com",
    "room_uuid": "live_12",
    "token": "eyJhbGciOiJIUzI1NiJ9..."
  }
}
```

| Field | Description |
|-------|-------------|
| `livekit_ws_url` | WebSocket URL for Flutter LiveKit client |
| `room_uuid` | LiveKit room to join (same room as astrologer) |
| `token` | JWT subscriber token (subscribe-only, cannot publish) |

**Flutter Usage (User App — full watching flow):**

Add to `pubspec.yaml`:
```yaml
dependencies:
  livekit_client: ^2.0.0
  flutter_webrtc: ^1.0.0
```

**Full watch function with video rendering:**
```dart
import 'package:livekit_client/livekit_client.dart';
import 'package:flutter/material.dart';

class LiveVideoWidget extends StatefulWidget {
  final String wsUrl;
  final String token;
  const LiveVideoWidget({super.key, required this.wsUrl, required this.token});
  @override
  State<LiveVideoWidget> createState() => _LiveVideoWidgetState();
}

class _LiveVideoWidgetState extends State<LiveVideoWidget> {
  late Room _room;
  RemoteVideoTrack? _remoteTrack;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _connectAndWatch();
  }

  Future<void> _connectAndWatch() async {
    try {
      _room = Room();
      _room.on<RoomEvent>(_onRoomEvent);
      await _room.connect(widget.wsUrl, widget.token);
      setState(() => _isLoading = false);
    } catch (e) {
      setState(() {
        _isLoading = false;
        _error = 'Failed to connect: $e';
      });
    }
  }

  void _onRoomEvent(RoomEvent event, dynamic data) {
    switch (event) {
      case RoomEvent.TrackSubscribed:
        final track = data as RemoteTrack;
        if (track is RemoteVideoTrack) {
          setState(() => _remoteTrack = track);
        }
        break;
      case RoomEvent.Disconnected:
        setState(() => _error = 'Broadcast ended');
        break;
      default:
        break;
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_error != null) {
      return Center(child: Text(_error!, style: const TextStyle(color: Colors.red)));
    }
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_remoteTrack == null) {
      return const Center(child: Text('Waiting for video...'));
    }
    return VideoRenderer(
      track: _remoteTrack!,
      fit: BoxFit.contain,
      mirror: false,
    );
  }

  @override
  void dispose() {
    _room.dispose();
    super.dispose();
  }
}

// Usage in your screen:
// LiveVideoWidget(wsUrl: data['livekit_ws_url'], token: data['token'])
```

**Error (400):**
```json
{
  "status": "error",
  "message": "Broadcast has not started yet"
}
```

> If `is_broadcasting` is false, this endpoint returns 400. The user should periodically check `is_broadcasting` or listen for the `AstrologerBroadcastStarted` event.
>
> Must call `POST /join` first before this endpoint.

## 6. Complete Step-by-Step Flows

### 6.1 Scheduled Live Session

```
Astrologer                API / Reverb + LiveKit          User(s)
    |                        |                               |
    |-- POST /astrologer/live -->|                           |
    |   {scheduled_at: ...}   |  201 Created (upcoming)      |
    |                        |                               |
    | ... time passes ...    |                               |
    |                        |                               |
    |-- POST /astrologer/live/{id}/start -->|                |
    |                        |  Status → ongoing             |
    |                        |  Broadcast: LiveSessionStarted|
    |                        |  on 'live-sessions' (public)  |
    |                        |  + Push notification          |
    |                        |                               |
    |-- POST /astrologer/live/{id}/broadcast -->|            |
    |                        |  LiveKit room created         |
    |<-- {livekit_ws_url,    |                               |
    |      room_uuid, token} |                               |
    |                        |  Broadcast: AstrologerBroadcast|
    |                        |  Started on 'live-session.{id}'|
    |                        |                               |
    |  Flutter: Connect to   |                               |
    |  LiveKit, publish video|                               |
    |                        |                               |
    |                        |  <-- GET /user/live/now ------|
    |                        |  <-- POST /user/live/{id}/join|
    |                        |  Broadcast: ViewerCountUpdated|
    |                        |  <-- POST /user/live/{id}/watch|
    |                        |  <-- {livekit_ws_url, token} --|
    |                        |                               |
    |                        |  User Flutter: Connect to     |
    |                        |  LiveKit, receive video       |
    |                        |                               |
    |                        |  <-- POST /user/live/{id}/comment |
    |                        |  Broadcast: NewLiveComment    |
    |                        |  <-- POST /user/live/{id}/super-chat |
    |                        |  Broadcast: SuperChatReceived |
    |                        |                               |
    |                        |  <-- POST /user/live/{id}/leave (opt) |
    |                        |  Broadcast: ViewerCountUpdated|
    |                        |                               |
    |-- POST /astrologer/live/{id}/stop -->|                  |
    |                        |  LiveKit room deleted         |
    |                        |  Status → completed           |
    |                        |  Broadcast: LiveSessionEnded  |
    |                        |  on 'live-sessions' + room    |
```

### 6.2 Instant Live Session

Same flow as scheduled, but skip the scheduling step:
1. `POST /astrologer/live` with `is_instant: true`
2. Session created as `ongoing` immediately
3. `LiveSessionStarted` broadcast + notification sent instantly
4. Astrologer calls `POST /astrologer/live/{id}/broadcast` to start LiveKit video
5. Users see it in `GET /user/live/now`
6. Same join/watch/comment/super-chat/leave/stop flow as scheduled

---

## 7. WebSocket / Real-Time Events

All events use `ShouldBroadcastNow` for immediate delivery. Channel names here are the **actual** Reverb channel names (Laravel Echo prefixes `.` for the event name when binding).

### 7.1 LiveSessionStarted

| Property | Value |
|----------|-------|
| **Channel** | `live-sessions` (Public Channel) |
| **Event Name** | `LiveSessionStarted` |
| **broadcastAs** | `LiveSessionStarted` |
| **Trigger** | Astrologer goes live (instant create OR scheduled start) |

**Payload:**
```json
{
  "id": 16,
  "title": "Instant Tarot Reading & QA",
  "astrologer": {
    "id": 5,
    "name": "Priya Sharma",
    "profile_photo": "photos/abc.jpg"
  },
  "viewer_count": 0,
  "is_broadcasting": false
}
```

> `is_broadcasting` is `false` initially. It becomes `true` after the astrologer calls `POST /broadcast`. User app should listen for `AstrologerBroadcastStarted` event to enable video UI.

---

### 7.2 ViewerCountUpdated

| Property | Value |
|----------|-------|
| **Channel** | `live-session.{id}` (Presence Channel) |
| **Event Name** | `ViewerCountUpdated` |
| **Trigger** | User joins or leaves a session |

**Payload:**
```json
{
  "live_session_id": 16,
  "viewer_count": 1
}
```

---

### 7.3 NewLiveComment

| Property | Value |
|----------|-------|
| **Channel** | `live-session.{id}` (Presence Channel) |
| **Event Name** | `NewLiveComment` |
| **Trigger** | User sends a comment |

**Payload:**
```json
{
  "user_id": 42,
  "user_name": "Rahul Kumar",
  "user_avatar": "photos/user42.jpg",
  "message": "Hello Priya, please answer my question!",
  "created_at": "2026-06-16T14:32:00.000000Z"
}
```

---

### 7.4 SuperChatReceived

| Property | Value |
|----------|-------|
| **Channel** | `live-session.{id}` (Presence Channel) |
| **Event Name** | `SuperChatReceived` |
| **Trigger** | User sends a gift tip |

**Payload:**
```json
{
  "user_id": 42,
  "user_name": "Rahul Kumar",
  "user_avatar": "photos/user42.jpg",
  "amount": 50.00,
  "message": "[Gift: Red Rose] Here is a Red Rose for you!",
  "gift": {
    "id": 3,
    "title": "Red Rose",
    "icon_url": "photos/gifts/red_rose.png"
  },
  "created_at": "2026-06-16T14:34:00.000000Z"
}
```

---

### 7.5 LiveSessionEnded

| Property | Value |
|----------|-------|
| **Channel** | `live-sessions` (Public) + `live-session.{id}` (Presence) |
| **Event Name** | `LiveSessionEnded` |
| **broadcastAs** | `LiveSessionEnded` |
| **Trigger** | Astrologer stops the session (`POST /stop`) |

**Payload:**
```json
{
  "id": 16,
  "astrologer_id": 5,
  "title": "Instant Tarot Reading & QA",
  "status": "ended"
}
```

**Flutter behavior on receiving this event:**
- Close LiveKit connection (if still connected)
- Hide video player
- Show "Session Ended" screen
- Disable comment/super-chat input

---

### 7.6 AstrologerBroadcastStarted

| Property | Value |
|----------|-------|
| **Channel** | `live-session.{id}` (Presence Channel) |
| **Event Name** | `AstrologerBroadcastStarted` |
| **broadcastAs** | `AstrologerBroadcastStarted` |
| **Trigger** | Astrologer calls `POST /broadcast` (LiveKit video activated) |

**Payload:**
```json
{
  "live_session_id": 16,
  "room_uuid": "live_16",
  "broadcast_started_at": "2026-06-16T10:00:00.000000Z"
}
```

**Flutter behavior on receiving this event:**
- Show "Video Available" indicator
- Enable "Watch" button
- Auto-call `POST /watch` to get subscriber token if user is already in the room

## 8. Presence Channel Auth

Defined in `routes/channels.php:53`:

```php
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
});
```

> The channel name is `live-session.{id}` (NOT `presence-live-session.{id}`). Laravel Echo automatically prefixes `presence-` when you use `Echo.join()`.

---

## 9. Frontend WebSocket Binding (Laravel Echo)

```javascript
// ==========================================
// 1. Listen to new/ended live streams (public)
// ==========================================
Echo.channel('live-sessions')
    .listen('.LiveSessionStarted', (e) => {
        console.log('New Stream Started:', e.title);
        // Prepend e to active streams list
    })
    .listen('.LiveSessionEnded', (e) => {
        console.log('Stream Ended:', e.title);
        // Remove from streams list
    });

// ==========================================
// 2. Join a specific live session (id = 16)
// ==========================================
Echo.join('live-session.16')
    // Presence callbacks
    .here((users) => {
        console.log('Current viewers:', users);
    })
    .joining((user) => {
        console.log('User joined:', user.name);
    })
    .leaving((user) => {
        console.log('User left:', user.name);
    })
    // Event listeners
    .listen('.ViewerCountUpdated', (e) => {
        console.log('Viewer count:', e.viewer_count);
        // Update viewer counter UI
    })
    .listen('.NewLiveComment', (e) => {
        console.log('New comment:', e.message);
        // Append to chat feed
    })
    .listen('.SuperChatReceived', (e) => {
        console.log('Super Chat:', e.gift.title, 'Amount:', e.amount);
        // Show animated overlay with e.gift.icon_url
    })
    .listen('.AstrologerBroadcastStarted', (e) => {
        console.log('Astrologer started broadcasting:', e.room_uuid);
        // Show "Watch" button / auto-get subscriber token
        // If user is already in room, call POST /watch
    })
    .listen('.LiveSessionEnded', (e) => {
        console.log('Session ended:', e.id);
        // Disconnect from LiveKit
        // Show "Session Ended" screen
        // Disable comment/super-chat input
    });
```

---

## 10. System Push Notification

When an astrologer goes live (instant or scheduled start), `notifyAllUsersAboutLive()` sends to ALL app users:

```
Title:  "Astrologer Live Now!"
Body:   "Priya Sharma is now streaming live. Join the session to ask your questions!"
Meta:   { "type": "live_session", "live_session_id": 16 }
```

---

## 11. Admin Routes

Web routes under `/admin/astrologers/live-sessions`:

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/admin/astrologers/live-sessions` | Paginated list (search, astrologer/status/type filters) |
| GET | `/admin/astrologers/live-sessions/{id}` | View session details |
| POST | `/admin/astrologers/live-sessions/{id}/status` | Update status (upcoming/ongoing/completed/cancelled) |
| DELETE | `/admin/astrologers/live-sessions/{id}` | Delete session |

---

## 12. Related Events (Reference)

These events exist for Chat and Call features (outside this guide's scope):

| Event | Channel | Trigger |
|-------|---------|---------|
| `ChatInitiated` | `private-user.{provider_id}` | User initiates a chat |
| `ChatAccepted` | `private-user.{consumer_id}` | Astrologer accepts chat |
| `ChatEnded` | `private-user.{receiverId}` | Chat ends with billing breakdown |
| `ChatDismissed` | `private-user.{receiverId}` | Chat cancelled / timed out |
| `ChatQueueUpdated` | `private-user.{providerId}` | Queue status change |
| `MessageSent` | `private-user.{receiverId}` | New chat message |
| `MessageStatusUpdated` | `private-user.{receiverId}` | Delivered/seen status update |
| `CallInitiated` | `private-user.{provider}` | User initiates a call |
| `CallAccepted` | `private-user.{consumer}` | Astrologer accepts call |
| `CallEnded` | `private-user.{receiver}` | Call ends |
| `PresenceUpdated` | `presence-room` | User online/offline pulse |

---

## 13. Flutter Integration Guide

### 13.1 Required Packages

Add to `pubspec.yaml`:
```yaml
dependencies:
  livekit_client: ^2.0.0
  flutter_webrtc: ^1.0.0
  laravel_echo: ^1.0.0
  pusher_client: ^1.0.0
  permission_handler: ^11.0.0
```

### 13.2 Laravel Echo Connection (Reverb WebSocket)

```dart
import 'package:laravel_echo/laravel_echo.dart';
import 'package:pusher_client/pusher_client.dart';

class EchoService {
  late Echo echo;
  final String authToken;

  EchoService(this.authToken);

  void connect() {
    echo = Echo(
      broadcaster: 'pusher',
      client: PusherClient(
        'astrology-key',
        PusherOptions(
          host: 'suryapathkundli.com',
          port: 443,
          scheme: 'https',
          encrypted: true,
          auth: Auth(
            endpoint: 'https://suryapathkundli.com/api/v1/broadcasting/auth',
            headers: {'Authorization': 'Bearer $authToken'},
          ),
        ),
      ),
    );
  }

  void joinLiveSession(int sessionId) {
    echo.join('live-session.$sessionId')
      .here((users) => debugPrint('Viewers here: $users'))
      .joining((user) => debugPrint('User joined: $user'))
      .leaving((user) => debugPrint('User left: $user'))
      .listen('.AstrologerBroadcastStarted', (e) {
        debugPrint('Broadcast started: ${e['room_uuid']}');
        // Call POST /watch to get LiveKit token
      })
      .listen('.LiveSessionEnded', (e) {
        debugPrint('Session ended: ${e['id']}');
        // Disconnect from LiveKit + show ended screen
      })
      .listen('.NewLiveComment', (e) {
        debugPrint('New comment: ${e['message']}');
        // Append to chat
      })
      .listen('.SuperChatReceived', (e) {
        debugPrint('SuperChat: ${e['gift']['title']}');
        // Show overlay animation
      })
      .listen('.ViewerCountUpdated', (e) {
        debugPrint('Viewer count: ${e['viewer_count']}');
        // Update counter
      });
  }

  void listenPublicStreams() {
    echo.channel('live-sessions')
      .listen('.LiveSessionStarted', (e) {
        debugPrint('New stream: ${e['title']}');
      })
      .listen('.LiveSessionEnded', (e) {
        debugPrint('Stream ended: ${e['title']}');
      });
  }

  void disconnect() {
    echo.leaveAllChannels();
    echo.disconnect();
  }
}
```

### 13.3 Astrologer App — Full Broadcast Flow

```dart
import 'package:livekit_client/livekit_client.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class AstrologerBroadcastService {
  late Room _room;
  bool _isBroadcasting = false;

  Future<Map<String, dynamic>> _apiCall(String url, String token) async {
    final res = await http.post(
      Uri.parse(url),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      },
    );
    if (res.statusCode != 200) {
      throw Exception('API error ${res.statusCode}: ${res.body}');
    }
    return jsonDecode(res.body)['data'];
  }

  Future<void> startBroadcast(int sessionId, String authToken) async {
    try {
      // 1. Request camera + mic permissions
      await [
        Permission.camera,
        Permission.microphone,
      ].request();

      // 2. Call API to create LiveKit room + get token
      final data = await _apiCall(
        'https://suryapathkundli.com/api/v1/astrologer/live/$sessionId/broadcast',
        authToken,
      );

      final wsUrl = data['livekit_ws_url'] as String;
      final roomUuid = data['room_uuid'] as String;
      final token = data['token'] as String;

      // 3. Connect to LiveKit room
      _room = Room();
      _room.on<RoomEvent>(_onRoomEvent);
      await _room.connect(wsUrl, token, roomOptions: RoomOptions(
        adaptiveStream: true,
        dynacast: true,
      ));

      // 4. Publish camera track
      final cameraTrack = await LocalVideoTrack.create();
      await _room.localParticipant?.publishTrack(cameraTrack);

      // 5. Publish microphone track
      final micTrack = await LocalAudioTrack.create();
      await _room.localParticipant?.publishTrack(micTrack);

      _isBroadcasting = true;
    } catch (e) {
      _isBroadcasting = false;
      debugPrint('Broadcast start failed: $e');
      rethrow;
    }
  }

  void _onRoomEvent(RoomEvent event, dynamic data) {
    switch (event) {
      case RoomEvent.Disconnected:
        _isBroadcasting = false;
        break;
      case RoomEvent.ParticipantConnected:
        debugPrint('Viewer joined: ${(data as RemoteParticipant).identity}');
        break;
      case RoomEvent.ParticipantDisconnected:
        debugPrint('Viewer left: ${(data as RemoteParticipant).identity}');
        break;
      default:
        break;
    }
  }

  Future<void> stopBroadcast(int sessionId, String authToken) async {
    try {
      await http.post(
        Uri.parse('https://suryapathkundli.com/api/v1/astrologer/live/$sessionId/stop-broadcast'),
        headers: {'Authorization': 'Bearer $authToken'},
      );
    } catch (_) {}
    if (_isBroadcasting) {
      await _room.disconnect();
      _isBroadcasting = false;
    }
  }

  void dispose() {
    _room.dispose();
  }
}
```

### 13.4 User App — Full Watch Flow

```dart
import 'package:livekit_client/livekit_client.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:flutter/material.dart';

class LiveKitViewer extends StatefulWidget {
  final int sessionId;
  final String authToken;
  const LiveKitViewer({super.key, required this.sessionId, required this.authToken});
  @override
  State<LiveKitViewer> createState() => _LiveKitViewerState();
}

class _LiveKitViewerState extends State<LiveKitViewer> {
  Room? _room;
  RemoteVideoTrack? _videoTrack;
  bool _isLoading = false;
  String? _error;

  Future<void> _connect() async {
    setState(() => _isLoading = true);

    try {
      // 1. Get subscriber token from API
      final res = await http.post(
        Uri.parse('https://suryapathkundli.com/api/v1/user/live/${widget.sessionId}/watch'),
        headers: {
          'Authorization': 'Bearer ${widget.authToken}',
          'Content-Type': 'application/json',
        },
      );
      if (res.statusCode != 200) {
        throw Exception('Watch API failed: ${res.body}');
      }
      final json = jsonDecode(res.body);
      if (json['status'] != 'success') {
        throw Exception(json['message'] ?? 'Watch API error');
      }
      final data = json['data'];
      final wsUrl = data['livekit_ws_url'] as String;
      final token = data['token'] as String;

      // 2. Connect to LiveKit (subscribe-only token)
      _room = Room();
      _room.on<RoomEvent>(_onRoomEvent);
      await _room!.connect(wsUrl, token);

      setState(() => _isLoading = false);
    } catch (e) {
      setState(() {
        _isLoading = false;
        _error = 'Connection failed: $e';
      });
    }
  }

  void _onRoomEvent(RoomEvent event, dynamic data) {
    switch (event) {
      case RoomEvent.TrackSubscribed:
        final track = data as RemoteTrack;
        if (track is RemoteVideoTrack && mounted) {
          setState(() => _videoTrack = track);
        }
        break;
      case RoomEvent.TrackUnsubscribed:
        if (data is RemoteVideoTrack && mounted) {
          setState(() => _videoTrack = null);
        }
        break;
      case RoomEvent.Disconnected:
        if (mounted) setState(() => _error = 'Broadcast ended');
        break;
      default:
        break;
    }
  }

  @override
  void initState() {
    super.initState();
    _connect();
  }

  @override
  Widget build(BuildContext context) {
    if (_error != null) {
      return Center(child: Text(_error!, style: const TextStyle(color: Colors.red)));
    }
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_videoTrack == null) {
      return const Center(child: Text('Waiting for broadcast...'));
    }
    return VideoRenderer(
      track: _videoTrack!,
      fit: BoxFit.contain,
      mirror: false,
    );
  }

  @override
  void dispose() {
    _room?.dispose();
    super.dispose();
  }
}

// Usage:
// LiveKitViewer(sessionId: 16, authToken: 'user-auth-token')
```

### 13.5 Handling Disconnection & Cleanup

```dart
class LiveSessionManager {
  EchoService? echoService;
  AstrologerBroadcastService? broadcastService;

  void onSessionEnded(int sessionId) {
    // 1. Disconnect from LiveKit
    broadcastService?.dispose();
    broadcastService = null;

    // 2. Leave Echo presence channel
    echoService?.echo.leave('live-session.$sessionId');

    // 3. Navigate to "Session Ended" screen
  }

  void onBroadcastStopped() {
    // Video stops but chat remains
    broadcastService?.dispose();
    broadcastService = null;
  }
}
```

### 13.6 Error Handling Checklist

| Scenario | Handling |
|----------|----------|
| Token expired (401 from LiveKit) | Re-call `/broadcast` or `/watch`, re-connect with fresh token |
| Network drop | Listen for `RoomEvent.Disconnected`, auto-reconnect with exponential backoff |
| Permission denied | Show dialog asking user to enable camera/mic in settings |
| API call fails | Retry with backoff (max 3 attempts), show user-friendly error |
| LiveKit room deleted | LiveKit disconnects all clients — show "Broadcast ended" screen |
| Concurrent broadcast | `/broadcast` returns existing token (idempotent) — just connect |
