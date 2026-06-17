# Live Session Comment Flow

Complete documentation of the real-time comment system for live sessions.

---

## 1. Step-by-Step Logic Flow

### 1.1 User Joins a Live Session

```
┌──────────────────────┐         ┌──────────────────────┐         ┌──────────────────────┐
│    Flutter App       │         │   Laravel Backend    │         │   Reverb (WebSocket) │
└──────┬───────────────┘         └──────┬───────────────┘         └──────────┬───────────┘
       │                                │                                   │
       │  1. echo.join('live-session.5')│                                   │
       │───────────────────────────────►│  POST /broadcasting/auth          │
       │                                │──────────────────────────────────►│
       │                                │                                   │
       │                                │  ◄── channel authorized ──────────│
       │  ◄── subscription confirmed ───│                                   │
       │                                │                                   │
       │  2. POST /live/5/join          │                                   │
       │───────────────────────────────►│                                   │
       │                                │  3. broadcast(UserJoinedLiveSession)│
       │                                │──────────────────────────────────►│
       │                                │                                   │
       │  ◄── 200 {session, comments}──│                                   │
       │                                │                                   │
       │  4. Render historical comments │                                   │
       │     from response.last_comments│                                   │
       │                                │                                   │
       │  ◄── UserJoinedLiveSession ────│───────────────────────────────────│
       │  (if subscribed before join)   │                                   │
       │                                │                                   │
       │  ◄── ViewerCountUpdated ───────│───────────────────────────────────│
```

**Critical Rule:** Step 1 MUST complete before Step 2. Otherwise the joining user misses `UserJoinedLiveSession`.

### 1.2 User Sends a Comment

```
┌──────────────────────┐         ┌──────────────────────┐         ┌──────────────────────┐
│    Flutter App       │         │   Laravel Backend    │         │   Reverb (WebSocket) │
└──────┬───────────────┘         └──────┬───────────────┘         └──────────┬───────────┘
       │                                │                                   │
       │  1. POST /live/5/comment       │                                   │
       │     {message: "Hello!"}        │                                   │
       │───────────────────────────────►│                                   │
       │                                │  2. Save LiveComment to DB        │
       │                                │                                   │
       │                                │  3. broadcast(NewLiveComment)     │
       │                                │     ->toOthers()                  │
       │                                │──────────────────────────────────►│
       │                                │                                   │
       │  ◄── 201 {id, message, ts} ────│                                   │
       │                                │                                   │
       │  4. Render "You: Hello!"       │                                   │
       │     from API response          │                                   │
       │                                │                                   │
       │  (sender does NOT receive      │                                   │
       │   broadcast via toOthers())    │                                   │
       │                                │                                   │
       │  ◄── NewLiveComment ───────────│───────────────────────────────────│
       │  (ALL OTHER subscribers)       │                                   │
       │                                │                                   │
       │  5. Others render              │                                   │
       │     "Rahul Kumar: Hello!"      │                                   │
```

### 1.3 Astrologer Receives Comments

```
┌──────────────────────┐         ┌──────────────────────┐         ┌──────────────────────┐
│  Astrologer Flutter  │         │   Laravel Backend    │         │   Reverb (WebSocket) │
└──────┬───────────────┘         └──────┬───────────────┘         └──────────┬───────────┘
       │                                │                                   │
       │  Already subscribed to         │                                   │
       │  live-session.{id} via Echo    │                                   │
       │                                │                                   │
       │  ◄── NewLiveComment ───────────│───────────────────────────────────│
       │  {user_id, user_name, message} │                                   │
       │                                │                                   │
       │  Render "{user_name}: {message}"                                    │
```

---

## 2. API Endpoints

### 2.1 Join Session

| Property | Value |
|----------|-------|
| **URL** | `POST /api/v1/user/live/{id}/join` |
| **Auth** | `Authorization: Bearer <sanctum_token>` |
| **Content-Type** | `application/json` |
| **Body** | None |
| **Success** | `200 OK` |

**Response:**
```json
{
  "status": "success",
  "message": "Joined live session successfully",
  "data": {
    "session": {
      "id": 16,
      "title": "Instant Tarot Reading & QA",
      "status": "ongoing",
      "is_broadcasting": true,
      "is_camera_on": true,
      "is_audio_on": true,
      "viewer_count": 5,
      "astrologer": {
        "id": 5,
        "name": "Priya Sharma",
        "profile_photo": "photos/abc.jpg",
        "gender": "female",
        "date_of_birth": "1990-05-15"
      }
    },
    "last_comments": [
      {
        "id": 42,
        "user_id": 10,
        "user_name": "Rahul",
        "user_avatar": "photos/rahul.jpg",
        "message": "Hello ji!",
        "created_at": "2026-06-17T10:30:00.000000Z"
      }
    ]
  }
}
```

### 2.2 Send Comment

| Property | Value |
|----------|-------|
| **URL** | `POST /api/v1/user/live/{id}/comment` |
| **Auth** | `Authorization: Bearer <sanctum_token>` |
| **Content-Type** | `application/json` |
| **Rate Limit** | `throttle:tiered` (30 requests per minute per user) |
| **Success** | `201 Created` |

**Request:**
```json
{
  "message": "Hello Priya, please answer my question!"
}
```

| Field | Required | Validation |
|-------|----------|------------|
| `message` | Yes | string, max:500 |

**Response:**
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

### 2.3 Get Comments (Paginated)

| Property | Value |
|----------|-------|
| **URL** | `GET /api/v1/user/live/{id}/comments?per_page=50` |
| **Auth** | `Authorization: Bearer <sanctum_token>` |
| **Success** | `200 OK` |

| Query | Required | Description |
|-------|----------|-------------|
| `per_page` | No | Items per page (default: 50, max: 100) |

**Response:**
```json
{
  "status": "success",
  "message": "Comments retrieved successfully",
  "data": {
    "data": [
      {
        "id": 42,
        "user_id": 10,
        "user_name": "Rahul",
        "user_avatar": "photos/rahul.jpg",
        "message": "Hello ji",
        "created_at": "2026-06-17T10:00:00.000000Z"
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

### 2.4 Astrologer Get Comments

| Property | Value |
|----------|-------|
| **URL** | `GET /api/v1/astrologer/live/{id}/comments?per_page=50` |
| **Auth** | `Authorization: Bearer <sanctum_token>` |
| **Success** | `200 OK` |

Same response format as 2.3.

---

## 3. Reverb WebSocket Events

### 3.1 NewLiveComment

| Property | Value |
|----------|-------|
| **Channel** | `live-session.{id}` (Presence Channel) |
| **Event Name** | `.NewLiveComment` |
| **`toOthers()`** | ✅ Yes — sender does NOT receive this event |

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

### 3.2 UserJoinedLiveSession

| Property | Value |
|----------|-------|
| **Channel** | `live-session.{id}` (Presence Channel) |
| **Event Name** | `.UserJoinedLiveSession` |
| **`toOthers()`** | ❌ No — joining user also receives this event |

**Payload:**
```json
{
  "user_id": 42,
  "user_name": "Rahul Kumar",
  "user_avatar": "photos/user42.jpg",
  "joined_at": "2026-06-17T10:00:00.000000Z"
}
```

### 3.3 UserLeftLiveSession

| Property | Value |
|----------|-------|
| **Channel** | `live-session.{id}` (Presence Channel) |
| **Event Name** | `.UserLeftLiveSession` |
| **`toOthers()`** | ❌ No |

**Payload:**
```json
{
  "user_id": 42,
  "user_name": "Rahul Kumar",
  "user_avatar": "photos/user42.jpg",
  "left_at": "2026-06-17T10:30:00.000000Z"
}
```

### 3.4 ViewerCountUpdated

| Property | Value |
|----------|-------|
| **Channel** | `live-session.{id}` (Presence Channel) |
| **Event Name** | `.ViewerCountUpdated` |
| **`toOthers()`** | ❌ No |

**Payload:**
```json
{
  "live_session_id": 16,
  "viewer_count": 5
}
```

### 3.5 SuperChatReceived

| Property | Value |
|----------|-------|
| **Channel** | `live-session.{id}` (Presence Channel) |
| **Event Name** | `.SuperChatReceived` |
| **`toOthers()`** | ❌ No |

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

## 4. Frontend Implementation Patterns

### 4.1 User: Join + Subscribe Echo Before API Call

```dart
// 1. Echo subscription MUST happen first
final channel = echoService.joinLiveSession(sessionId, currentUserId: userId);

// 2. Wait for .here callback (subscription confirmed), then call join API
channel.here((users) async {
  final res = await http.post(
    Uri.parse('https://suryapathkundli.com/api/v1/user/live/$sessionId/join'),
    headers: {'Authorization': 'Bearer $authToken'},
  );
  if (res.statusCode == 200) {
    final data = jsonDecode(res.body)['data'];
    // Render session details + last_comments
  }
});
```

### 4.2 User: Send Comment with Debounce

```dart
bool _isSending = false;

Future<void> _sendComment() async {
  if (_isSending) return; // ← Debounce guard
  _isSending = true;
  try {
    final res = await http.post(
      Uri.parse('https://suryapathkundli.com/api/v1/user/live/$sessionId/comment'),
      headers: {
        'Authorization': 'Bearer $authToken',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({'message': _controller.text.trim()}),
    );
    if (res.statusCode == 201) {
      _controller.clear();
      final data = jsonDecode(res.body)['data'];
      // Add "You: message" to chat locally
      addChatMessage(userId, 'You', data['message'], data['created_at']);
    }
  } finally {
    _isSending = false;
  }
}
```

### 4.3 User: Handle Incoming NewLiveComment

```dart
.listen('.NewLiveComment', (e) {
  // Safety check: backend uses toOthers(), but guard here too
  if (e['user_id'] == currentUserId) return;

  addChatMessage(
    e['user_id'],
    e['user_name'],
    e['message'],
    e['created_at'],
  );
});
```

### 4.4 User: Handle UserJoinedLiveSession

```dart
.listen('.UserJoinedLiveSession', (e) {
  addSystemMessage('${e['user_name']} joined');
});
```

### 4.5 Astrologer: Single Echo, Reused Across Widgets

```dart
// In AstrologerBroadcastService:
Echo? echo;

Echo _connectEcho(String authToken, int sessionId) {
  echo = Echo(
    broadcaster: 'pusher',
    client: PusherClient('astrology-key', PusherOptions(
      host: 'suryapathkundli.com',
      port: 443,
      scheme: 'https',
      encrypted: true,
      auth: Auth(
        endpoint: 'https://suryapathkundli.com/api/v1/broadcasting/auth',
        headers: {'Authorization': 'Bearer $authToken'},
      ),
    )),
  );

  echo!.join('live-session.$sessionId')
    .here((users) => debugPrint('Viewers: $users'))
    .joining((user) => debugPrint('Joined: $user'))
    .leaving((user) => debugPrint('Left: $user'))
    .listen('.SuperChatReceived', (e) { /* handle */ })
    .listen('.LiveSessionEnded', (e) { _disconnectAll(); })
    .listen('.ViewerCountUpdated', (e) { /* update counter */ });

  return echo!; // ← Return for other widgets
}

// In AstrologerChatScreen — receive shared Echo via constructor:
class AstrologerChatScreen extends StatefulWidget {
  final Echo echo; // ← Shared, NOT a new connection
  // ...
}
```

---

## 5. Security & Rate Limiting

| Measure | Implementation | Details |
|---------|---------------|---------|
| **Authentication** | `auth:sanctum` middleware | All comment endpoints require valid Sanctum token |
| **Channel Authorization** | `Broadcast::channel('live-session.{id}', ...)` | Only authenticated users with `sanctum` guard can subscribe; session must be `ongoing` |
| **Rate Limiting** | `throttle:tiered` (30/min) | Applied to `POST /live/{id}/comment` route |
| **Frontend Debounce** | `_isSending` boolean guard | Prevents rapid duplicate sends |
| **Broadcast Exclusions** | `->toOthers()` on `NewLiveComment` | Sender excluded from own comment broadcast |
| **Input Validation** | `message: required\|string\|max:500` | Max 500 chars, server-side validation |

---

## 6. Changelog

| Date | Change | Details |
|------|--------|---------|
| 2026-06-17 | Added `->toOthers()` to `NewLiveComment` | Fixed duplicate comments for sender |
| 2026-06-17 | Explicit throttle on comment route | `POST /live/{id}/comment` now has explicit `throttle:tiered` (30/min) |
| 2026-06-17 | Consolidated Echo connections | Astrologer now uses single shared Echo instance |
| 2026-06-17 | Join ordering documented | Echo subscription must precede API call |
| 2026-06-17 | Comment send debounce added | `_isSending` guard in send function |
