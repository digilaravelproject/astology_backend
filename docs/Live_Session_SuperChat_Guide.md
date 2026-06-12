# Live Session & Super Chat API Guide

## Overview

This document covers the **Live Streaming** (Instagram Live style) feature and **Super Chat** paid-tip system. Astrologers can go live with video streaming, multiple users can join as viewers, send real-time comments, and send paid Super Chats that deduct from their wallet and credit the astrologer.

---

## Architecture

### Real-Time Stack
- **Laravel Reverb** (WebSockets) — all real-time communication
- **Presence Channels** (`live-session.{id}`) — viewer count, comments, super chats
- **Private Channels** (`call.{sessionId}`, `user.{id}`) — 1-on-1 call signaling

### Database Tables
| Table | Purpose |
|-------|---------|
| `live_sessions` | Live stream sessions (astrologer, status, stream_key, viewer_count) |
| `live_comments` | Viewer comments on live sessions |
| `super_chats` | Paid tips with wallet transaction tracking |

### Wallet Integration
- User wallet is debited on Super Chat send
- Astrologer wallet (via `astrologer.user_id`) is credited simultaneously
- All wallet operations use **ACID-compliant transactions** with `lockForUpdate()` to prevent race conditions
- Both debit and credit reference the `super_chats` record for full traceability

---

## API Endpoints

### Authentication
All endpoints require `Authorization: Bearer <token>` header. Tokens are obtained via Sanctum login.

### Astrologer Endpoints

#### 1. List My Live Sessions
```
GET /api/v1/astrologer/live?filter=upcoming|completed|all
```
Response includes paginated sessions.

#### 2. Create Live Session
```
POST /api/v1/astrologer/live
Content-Type: application/json
{
  "title": "Evening Tarot Reading",
  "description": "Join me for free tarot!",
  "scheduled_at": "2026-06-15 18:00:00",
  "session_type": "public",
  "duration_minutes": 60,
  "max_participants": 500
}
```

#### 3. Start Live Session
```
POST /api/v1/astrologer/live/{id}/start
```
- Changes status from `upcoming` → `ongoing`
- Sets `started_at` timestamp
- Generates unique `stream_key` for streaming
- Returns updated session

#### 4. Stop Live Session
```
POST /api/v1/astrologer/live/{id}/stop
```
- Changes status from `ongoing` → `completed`
- Sets `ended_at` timestamp
- Calculates `duration_minutes`
- Returns updated session

#### 5. Update Live Session
```
PUT /api/v1/astrologer/live/{id}
```
#### 6. Delete Live Session
```
DELETE /api/v1/astrologer/live/{id}
```

---

### User (Viewer) Endpoints

#### 1. Get Currently Streaming Sessions
```
GET /api/v1/user/live/now
```
Returns all public ongoing live sessions with astrologer info.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Evening Tarot Reading",
      "astrologer": {
        "id": 5,
        "name": "Priya Sharma",
        "profile_photo": "storage/photos/abc.jpg"
      },
      "viewer_count": 42,
      "started_at": "2026-06-12T18:00:00.000000Z"
    }
  ],
  "message": "Live sessions retrieved successfully"
}
```

#### 2. Get Live Session Detail
```
GET /api/v1/user/live/{id}
```
Returns session details + astrologer profile.

#### 3. Join Live Session
```
POST /api/v1/user/live/{id}/join
```
Increments `viewer_count`. No request body needed.

#### 4. Leave Live Session
```
POST /api/v1/user/live/{id}/leave
```
Decrements `viewer_count`.

#### 5. Send Comment
```
POST /api/v1/user/live/{id}/comment
Content-Type: application/json
{
  "message": "Amazing session! 🌟"
}
```
**Response:** `201 Created`
```json
{
  "success": true,
  "data": {
    "id": 99,
    "message": "Amazing session! 🌟",
    "created_at": "2026-06-12T18:05:30.000000Z"
  },
  "message": "Comment sent successfully"
}
```
Broadcasts `NewLiveComment` event to `live-session.{id}` presence channel.

#### 6. Send Super Chat
```
POST /api/v1/user/live/{id}/super-chat
Content-Type: application/json
{
  "amount": 50,
  "message": "Great reading! 🎉"
}
```
**Response:** `201 Created`
```json
{
  "success": true,
  "data": {
    "id": 10,
    "amount": "50.00",
    "message": "Great reading! 🎉",
    "created_at": "2026-06-12T18:06:00.000000Z"
  },
  "message": "Super Chat sent successfully"
}
```
**Error (insufficient balance):** `402 Payment Required`
```json
{
  "success": false,
  "message": "Insufficient balance in your wallet."
}
```

#### 7. Get Session Comments
```
GET /api/v1/user/live/{id}/comments?per_page=50
```
Returns paginated comments.

---

## Reverb Channels

### Presence Channel: `live-session.{id}`

Used for all live session real-time communication. Presence channels automatically track who is connected.

**Authorization:** Only authenticated users can subscribe, and only for sessions with `status = 'ongoing'`.

**Subscribe (client-side):**
```javascript
// Using Pusher JS with Reverb
const channel = pusher.subscribe('presence-live-session.1');

channel.bind('pusher:subscription_succeeded', (members) => {
  console.log('Viewer count:', members.count);
  members.each((member) => {
    console.log('Viewer:', member.info.name);
  });
});

channel.bind('pusher:member_added', (member) => {
  // A new viewer joined
});

channel.bind('pusher:member_removed', (member) => {
  // A viewer left
});
```

### Events to Listen On

#### 1. `NewLiveComment`
```json
{
  "user_id": 42,
  "user_name": "Rahul K",
  "user_avatar": "storage/photos/user42.jpg",
  "message": "Amazing session! 🌟",
  "created_at": "2026-06-12T18:05:30.000000Z"
}
```
Display this in the chat feed on the live stream.

#### 2. `SuperChatReceived`
```json
{
  "user_id": 42,
  "user_name": "Rahul K",
  "user_avatar": "storage/photos/user42.jpg",
  "amount": 50.00,
  "message": "Great reading! 🎉",
  "created_at": "2026-06-12T18:06:00.000000Z"
}
```
Trigger a flashy animation (e.g., message flies across screen, shows amount with sparkle effect) when this event is received.

---

## Super Chat Transaction Flow

```
User POST /live/{id}/super-chat { amount: 50, message: "Great!" }
  │
  ├─► Validate session is 'ongoing'
  ├─► Validate amount > 0
  │
  ├─► DB::beginTransaction()
  │    ├─► lockForUpdate() user.wallet
  │    ├─► lockForUpdate() astrologer.wallet (via user_id)
  │    ├─► Check user.balance >= 50
  │    ├─► INSERT super_chats (status: pending)
  │    ├─► walletRepository.debit(user, 50)
  │    │    └─► WalletTransaction (debit, super_chat_deduction)
  │    ├─► walletRepository.credit(astrologer_user, 50)
  │    │    └─► WalletTransaction (credit, super_chat_credit)
  │    ├─► UPDATE super_chats SET status=completed, wallet_transaction_id=...
  │    └─► DB::commit()
  │
  ├─► Broadcast SuperChatReceived event to live-session.{id}
  │
  └─► Return 201 { id, amount, message, created_at }
```

### Wallet Transaction Records

When a Super Chat is processed, two wallet transactions are created:

| Transaction | Type | Description |
|-------------|------|-------------|
| User Debit | `debit` | `"Super Chat from Rahul K to Astrologer Priya Sharma"` |
| Astrologer Credit | `credit` | `"Super Chat from Rahul K to Astrologer Priya Sharma"` |

Both transactions reference the `SuperChat` model record via `reference_type` / `reference_id`.

---

## Error Codes

| HTTP Code | Meaning |
|-----------|---------|
| 400 | Validation failed or session not active |
| 402 | Insufficient wallet balance (Super Chat only) |
| 403 | Not authorized (not an astrologer, not your session) |
| 404 | Live session not found |
| 422 | Wrong session status for operation |
| 500 | Server error |

---

## Frontend Implementation Notes

### Live Stream Screen Components

1. **Video Player** — Display `stream_url` from session detail
2. **Viewer Count** — Read from `pusher:subscription_succeeded` member count or initial API response
3. **Comment Feed** — Subscribe to `NewLiveComment` event, append each to chat list
4. **Super Chat Animation** — On `SuperChatReceived`, show animated overlay with user name + amount + message
5. **Comment Input** — POST to `/live/{id}/comment`
6. **Super Chat Input** — POST to `/live/{id}/super-chat`

### Presence vs Private Channels

| Feature | Channel Type | Why |
|---------|-------------|-----|
| Comments | `live-session.{id}` (Presence) | All viewers see all comments |
| Super Chats | `live-session.{id}` (Presence) | All viewers see the flashy animation |
| Call Signaling | `user.{id}` (Private) + `call.{id}` (Private) | Only the two participants |

### Stream Key Usage

When an astrologer starts a session via `POST /live/{id}/start`, the response includes a `stream_key`. This key is used to authenticate the RTMP stream push to the streaming server (e.g., Ant Media, Wowza, or custom RTMP server). The `stream_url` field (set separately via admin or auto-generated based on config) is the CDN endpoint that viewers use to watch the stream.

---

## Changelog

| Date | Change |
|------|--------|
| 2026-06-12 | Initial live session + super chat implementation |
