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
| `live_url` | string / null | RTMP ingest URL |
| `stream_key` | string / null | Stream key (32 chars random) |
| `stream_url` | string / null | Playback URL |
| `started_at` | string / null (Y-m-d H:i:s) | Actual start time |
| `ended_at` | string / null (Y-m-d H:i:s) | Actual end time |
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
    "live_url": null,
    "stream_key": null,
    "stream_url": null,
    "started_at": null,
    "ended_at": null,
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

> When `is_instant=true`, `scheduled_at` is ignored (not required). Session is created with `status=ongoing`, `started_at=now`, `stream_key=random(32)`, and `LiveSessionStarted` is broadcast immediately.

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
    "live_url": null,
    "stream_key": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
    "stream_url": null,
    "started_at": "2026-06-16 14:30:00",
    "ended_at": null,
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
    "started_at": "2026-06-20 18:00:05",
    "stream_key": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
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

No request body. Duration is calculated automatically: `started_at->diffInMinutes(now)`.

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Live session ended successfully",
  "data": {
    "id": 15,
    "status": "completed",
    "ended_at": "2026-06-20 19:15:00",
    "duration_minutes": 75,
    ...
  }
}
```

> Only `ongoing` sessions can be stopped (422 for `upcoming`/`completed`).

---

### 4.9 Get Current Active Session (Recovery)

**GET** `/api/v1/astrologer/live/current`

Used for crash recovery: if the astrologer's app closes, they fetch the current session to recover `stream_key` and reconnect RTMP.

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

## 5. User (Viewer) API

Base: `/api/v1/user/live` — all routes require `auth:sanctum` + `throttle:tiered`.

### 5.1 List Active Streams

**GET** `/api/v1/user/live/now`

No request body. Returns only public, ongoing sessions, ordered by latest `started_at`.

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
        "profile_photo": "https://astrogravity.com/storage/photos/abc.jpg"
      },
      "viewer_count": 0,
      "started_at": "2026-06-16T14:30:00.000000Z"
    }
  ]
}
```

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
    "stream_url": null,
    "viewer_count": 0,
    "started_at": "2026-06-16T14:30:00.000000Z",
    "astrologer": {
      "id": 5,
      "name": "Priya Sharma",
      "profile_photo": "https://astrogravity.com/storage/photos/abc.jpg",
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

> Error (400): `"Live session is not currently active"` if status is not `ongoing`.

---

### 5.4 Leave Session

**POST** `/api/v1/user/live/{id}/leave`

No request body. Decrements `viewer_count`.

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
        "user_avatar": "https://astrogravity.com/storage/photos/user42.jpg",
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

## 6. Complete Step-by-Step Flows

### 6.1 Scheduled Live Session

```
Astrologer                API / Reverb                    User(s)
    |                        |                               |
    |-- POST /astrologer/live -->|                           |
    |   {scheduled_at: ...}   |  201 Created (upcoming)      |
    |                        |                               |
    | ... time passes ...    |                               |
    |                        |                               |
    |-- POST /astrologer/live/{id}/start -->|                |
    |                        |  Broadcast: LiveSessionStarted
    |                        |  on 'live-sessions' (public)  |
    |                        |  + Push notification          |
    |                        |                               |
    |                        |  <-- GET /user/live/now ------|
    |                        |  <-- POST /user/live/{id}/join|
    |                        |  Broadcast: ViewerCountUpdated|
    |                        |  on 'live-session.{id}'       |
    |                        |                               |
    |                        |  <-- POST /user/live/{id}/comment |
    |                        |  Broadcast: NewLiveComment    |
    |                        |  on 'live-session.{id}'       |
    |                        |                               |
    |                        |  <-- POST /user/live/{id}/super-chat |
    |                        |  Broadcast: SuperChatReceived |
    |                        |  on 'live-session.{id}'       |
    |                        |                               |
    |                        |  <-- POST /user/live/{id}/leave (opt) |
    |                        |  Broadcast: ViewerCountUpdated|
    |                        |                               |
    |-- POST /astrologer/live/{id}/stop -->|                 |
    |                        |  status -> completed          |
```

### 6.2 Instant Live Session

Same flow as scheduled, but skip the scheduling step:
1. `POST /astrologer/live` with `is_instant: true`
2. Session created as `ongoing` immediately
3. `LiveSessionStarted` broadcast + notification sent instantly
4. Users see it in `GET /user/live/now`
5. Same join/comment/super-chat/leave/stop flow as scheduled

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
    "profile_photo": "https://astrogravity.com/storage/photos/abc.jpg"
  },
  "viewer_count": 0,
  "started_at": "2026-06-16T14:30:00.000000Z"
}
```

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
  "user_avatar": "https://astrogravity.com/storage/photos/user42.jpg",
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
  "user_avatar": "https://astrogravity.com/storage/photos/user42.jpg",
  "amount": 50.00,
  "message": "[Gift: Red Rose] Here is a Red Rose for you!",
  "gift": {
    "id": 3,
    "title": "Red Rose",
    "icon_url": "https://astrogravity.com/storage/gifts/icons/red_rose.png"
  },
  "created_at": "2026-06-16T14:34:00.000000Z"
}
```

---

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
        'profile_photo' => $user->profile_photo_url,
    ];
});
```

> The channel name is `live-session.{id}` (NOT `presence-live-session.{id}`). Laravel Echo automatically prefixes `presence-` when you use `Echo.join()`.

---

## 9. Frontend WebSocket Binding (Laravel Echo)

```javascript
// ==========================================
// 1. Listen to new live streams (public)
// ==========================================
Echo.channel('live-sessions')
    .listen('.LiveSessionStarted', (e) => {
        console.log('New Stream Started:', e.title);
        // Prepend e to active streams list
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
