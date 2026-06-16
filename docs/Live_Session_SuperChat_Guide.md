# Live Session & Super Chat API Guide

## Overview

This document covers the **Live Streaming** (Instagram Live style) feature and **Super Chat** paid-tip system. Astrologers can go live instantly or schedule sessions for the future, multiple users can join as viewers, send real-time comments, and send paid Super Chats that deduct from their wallet and credit the astrologer.

---

## Architecture

### Real-Time Stack
- **Laravel Reverb** (WebSockets) — all real-time communication
- **Presence Channels** (`live-session.{id}`) — viewer count, comments, super chats
- **Public Channels** (`live-sessions`) — updates when any astrologer goes live
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
- To prevent deadlocks, database locks on user and astrologer wallets are always acquired in deterministic order (ascending user ID)
- Both debit and credit reference the `super_chats` record for full traceability

---

## API Endpoints

### Authentication
All endpoints require `Authorization: Bearer <token>` header. Tokens are obtained via Sanctum login.

---

### Astrologer Endpoints

#### 1. List My Live Sessions
```http
GET /api/v1/astrologer/live?filter=upcoming|completed|all&per_page=15
```

##### Query Parameters
- `filter` (optional, string): Filter by session status. Options: `upcoming`, `completed`, `all`. Defaults to `all`.
- `per_page` (optional, integer): Number of records per page. Defaults to `15`.

##### Response (Success - `filter=all`):
```json
{
  "success": true,
  "data": {
    "upcoming": {
      "data": [
        {
          "id": 2,
          "astrologer_id": 5,
          "title": "Weekly Tarot Prediction",
          "description": "Tarot guidance for the next week",
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
          "max_participants": 100,
          "current_participants": 0,
          "viewer_count": 0,
          "created_at": "2026-06-16 12:00:00",
          "updated_at": "2026-06-16 12:00:00"
        }
      ],
      "total": 1
    },
    "completed": {
      "data": [
        {
          "id": 1,
          "astrologer_id": 5,
          "title": "Evening Meditation",
          "description": "Relaxing evening meditation",
          "scheduled_at": "2026-06-15 19:00:00",
          "scheduled_date": "2026-06-15",
          "scheduled_time": "19:00:00",
          "session_type": "public",
          "status": "completed",
          "live_url": "https://stream.astrogravity.com/live/evening-meditation",
          "stream_key": "some_random_stream_key_32_chars",
          "stream_url": "rtmp://stream.astrogravity.com/live",
          "started_at": "2026-06-15 19:05:00",
          "ended_at": "2026-06-15 20:05:00",
          "duration_minutes": 60,
          "max_participants": 200,
          "current_participants": 45,
          "viewer_count": 0,
          "created_at": "2026-06-15 15:00:00",
          "updated_at": "2026-06-15 20:05:00"
        }
      ],
      "pagination": {
        "current_page": 1,
        "total_pages": 1,
        "per_page": 15,
        "total": 1
      }
    }
  },
  "message": "Live sessions retrieved successfully"
}
```

---

#### 2. Get Currently Active Ongoing Session
*Allows the astrologer to check if they have a running live stream (e.g. if the app closed accidentally or they need to resume).*
```http
GET /api/v1/astrologer/live/current
```

##### Response (Success - Session Found):
```json
{
  "success": true,
  "data": {
    "id": 13,
    "astrologer_id": 5,
    "title": "Instant QA & Reading",
    "description": "Going live right now to answer your questions!",
    "scheduled_at": "2026-06-16 14:06:00",
    "scheduled_date": "2026-06-16",
    "scheduled_time": "14:06:00",
    "session_type": "public",
    "status": "ongoing",
    "live_url": null,
    "stream_key": "abcde12345randomstreamkeyhere",
    "stream_url": null,
    "started_at": "2026-06-16 14:06:00",
    "ended_at": null,
    "duration_minutes": 45,
    "max_participants": 200,
    "current_participants": 12,
    "viewer_count": 12,
    "created_at": "2026-06-16 14:06:00",
    "updated_at": "2026-06-16 14:08:00"
  },
  "message": "Current active live session retrieved successfully"
}
```

##### Response (Success - No Session Active):
```json
{
  "success": true,
  "data": null,
  "message": "No active live session found"
}
```

---

#### 3. Create Live Session (Scheduled or Instant)
```http
POST /api/v1/astrologer/live
Content-Type: application/json
```

##### Payload Body Parameters
- `title` (required, string): Maximum 255 characters.
- `description` (optional, string): Maximum 1000 characters.
- `is_instant` (optional, boolean): If `true`, the live session starts immediately. Status becomes `ongoing`, `started_at` is set, and a random `stream_key` is generated.
- `scheduled_at` (required unless `is_instant` is true, string): Format `Y-m-d H:i:s`. Must be a future date/time.
- `session_type` (required, string): Options: `public`, `private`.
- `duration_minutes` (optional, integer): Range `15` to `480`. Defaults to `60`.
- `max_participants` (optional, integer): Range `1` to `5000`. Defaults to `100`.

##### Example Request (Instant Live):
```json
{
  "title": "Instant QA & Reading",
  "description": "Going live right now to answer your questions!",
  "is_instant": true,
  "session_type": "public",
  "duration_minutes": 45,
  "max_participants": 200
}
```

##### Response (Success - Instant - `201 Created`):
```json
{
  "success": true,
  "data": {
    "id": 13,
    "astrologer_id": 5,
    "title": "Instant QA & Reading",
    "description": "Going live right now to answer your questions!",
    "scheduled_at": "2026-06-16 14:06:00",
    "scheduled_date": "2026-06-16",
    "scheduled_time": "14:06:00",
    "session_type": "public",
    "status": "ongoing",
    "live_url": null,
    "stream_key": "abcde12345randomstreamkeyhere",
    "stream_url": null,
    "started_at": "2026-06-16 14:06:00",
    "ended_at": null,
    "duration_minutes": 45,
    "max_participants": 200,
    "current_participants": 0,
    "viewer_count": 0,
    "created_at": "2026-06-16 14:06:00",
    "updated_at": "2026-06-16 14:06:00"
  },
  "message": "Live session created successfully"
}
```

---

#### 4. Start Live Session (For Scheduled Upcoming Sessions)
```http
POST /api/v1/astrologer/live/{id}/start
```

##### Response (Success):
```json
{
  "success": true,
  "data": {
    "id": 12,
    "astrologer_id": 5,
    "title": "Evening Tarot Reading",
    "description": "Join me for free tarot!",
    "scheduled_at": "2026-06-25 18:00:00",
    "scheduled_date": "2026-06-25",
    "scheduled_time": "18:00:00",
    "session_type": "public",
    "status": "ongoing",
    "live_url": null,
    "stream_key": "streamkeyxyz123abc456random",
    "stream_url": null,
    "started_at": "2026-06-16 14:10:00",
    "ended_at": null,
    "duration_minutes": 60,
    "max_participants": 500,
    "current_participants": 0,
    "viewer_count": 0,
    "created_at": "2026-06-16 14:05:00",
    "updated_at": "2026-06-16 14:10:00"
  },
  "message": "Live session started successfully"
}
```

---

#### 5. Stop Live Session
```http
POST /api/v1/astrologer/live/{id}/stop
```

##### Response (Success):
```json
{
  "success": true,
  "data": {
    "id": 12,
    "astrologer_id": 5,
    "title": "Evening Tarot Reading",
    "description": "Join me for free tarot!",
    "scheduled_at": "2026-06-25 18:00:00",
    "scheduled_date": "2026-06-25",
    "scheduled_time": "18:00:00",
    "session_type": "public",
    "status": "completed",
    "live_url": null,
    "stream_key": "streamkeyxyz123abc456random",
    "stream_url": null,
    "started_at": "2026-06-16 14:10:00",
    "ended_at": "2026-06-16 14:55:00",
    "duration_minutes": 45,
    "max_participants": 500,
    "current_participants": 0,
    "viewer_count": 0,
    "created_at": "2026-06-16 14:05:00",
    "updated_at": "2026-06-16 14:55:00"
  },
  "message": "Live session ended successfully"
}
```

---

#### 6. Update Live Session
```http
PUT /api/v1/astrologer/live/{id}
Content-Type: application/json
```

##### Payload Body Parameters
- `title` (optional, string): Max 255.
- `description` (optional, string): Max 1000.
- `scheduled_at` (optional, string): Format `Y-m-d H:i:s`. Must be a future date/time.
- `session_type` (optional, string): `public`, `private`.
- `status` (optional, string): `upcoming`, `ongoing`, `completed`, `cancelled`.
- `duration_minutes` (optional, integer): Range `15` to `480`.
- `max_participants` (optional, integer): Range `1` to `5000`.

##### Response (Success):
```json
{
  "success": true,
  "data": {
    "id": 12,
    "astrologer_id": 5,
    "title": "Updated Tarot Time",
    "description": "Join me for free tarot!",
    "scheduled_at": "2026-06-25 18:00:00",
    "scheduled_date": "2026-06-25",
    "scheduled_time": "18:00:00",
    "session_type": "public",
    "status": "upcoming",
    "live_url": null,
    "stream_key": null,
    "stream_url": null,
    "started_at": null,
    "ended_at": null,
    "duration_minutes": 90,
    "max_participants": 500,
    "current_participants": 0,
    "viewer_count": 0,
    "created_at": "2026-06-16 14:05:00",
    "updated_at": "2026-06-16 14:15:00"
  },
  "message": "Live session updated successfully"
}
```

---

#### 7. Delete Live Session
```http
DELETE /api/v1/astrologer/live/{id}
```

##### Response (Success):
```json
{
  "success": true,
  "data": null,
  "message": "Live session 'Updated Tarot Time' deleted successfully"
}
```

---

### User (Viewer) Endpoints

#### 1. Get Currently Streaming Sessions
```http
GET /api/v1/user/live/now
```

##### Response (Success):
```json
{
  "success": true,
  "data": [
    {
      "id": 13,
      "title": "Instant QA & Reading",
      "astrologer": {
        "id": 5,
        "name": "Priya Sharma",
        "profile_photo": "https://astrogravity.com/storage/photos/abc.jpg"
      },
      "viewer_count": 42,
      "started_at": "2026-06-16T14:06:00.000000Z"
    }
  ],
  "message": "Live sessions retrieved successfully"
}
```

---

#### 2. Get Live Session Detail
```http
GET /api/v1/user/live/{id}
```

##### Response (Success):
```json
{
  "success": true,
  "data": {
    "id": 13,
    "title": "Instant QA & Reading",
    "description": "Going live right now to answer your questions!",
    "session_type": "public",
    "status": "ongoing",
    "stream_url": "https://stream.astrogravity.com/live/13.m3u8",
    "viewer_count": 42,
    "started_at": "2026-06-16T14:06:00.000000Z",
    "astrologer": {
      "id": 5,
      "name": "Priya Sharma",
      "profile_photo": "https://astrogravity.com/storage/photos/abc.jpg",
      "gender": "female",
      "date_of_birth": "1990-08-15"
    }
  },
  "message": "Live session retrieved successfully"
}
```

---

#### 3. Join Live Session
```http
POST /api/v1/user/live/{id}/join
```
*Increments the viewer count for the session and broadcasts a `ViewerCountUpdated` event.*

##### Response (Success):
```json
{
  "success": true,
  "data": null,
  "message": "Joined live session successfully"
}
```

---

#### 4. Leave Live Session
```http
POST /api/v1/user/live/{id}/leave
```
*Decrements the viewer count for the session and broadcasts a `ViewerCountUpdated` event.*

##### Response (Success):
```json
{
  "success": true,
  "data": null,
  "message": "Left live session successfully"
}
```

---

#### 5. Send Comment
```http
POST /api/v1/user/live/{id}/comment
Content-Type: application/json
```

##### Payload Body Parameters
- `message` (required, string): Maximum 500 characters.

##### Example Request:
```json
{
  "message": "Amazing session! 🌟"
}
```

##### Response (Success - `201 Created`):
```json
{
  "success": true,
  "data": {
    "id": 99,
    "message": "Amazing session! 🌟",
    "created_at": "2026-06-16T14:20:00.000000Z"
  },
  "message": "Comment sent successfully"
}
```

---

#### 6. Send Super Chat
```http
POST /api/v1/user/live/{id}/super-chat
Content-Type: application/json
```

##### Payload Body Parameters
- `gift_id` (required, integer): The ID of the active gift to send as a Super Chat.
- `message` (optional, string): Maximum 500 characters.

##### Example Request:
```json
{
  "gift_id": 3,
  "message": "Great reading! 🎉"
}
```

##### Response (Success - `201 Created`):
```json
{
  "success": true,
  "data": {
    "id": 10,
    "amount": 50.00,
    "message": "[Gift: Red Rose] Great reading! 🎉",
    "created_at": "2026-06-16T14:22:00.000000Z"
  },
  "message": "Super Chat sent successfully"
}
```

---

#### 7. Get Session Comments
```http
GET /api/v1/user/live/{id}/comments?per_page=50
```

##### Query Parameters
- `per_page` (optional, integer): Range `1` to `100`. Defaults to `50`.

##### Response (Success):
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 99,
        "user_id": 42,
        "user_name": "Rahul K",
        "user_avatar": "https://astrogravity.com/storage/photos/user42.jpg",
        "message": "Amazing session! 🌟",
        "created_at": "2026-06-16T14:20:00.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "per_page": 50,
      "total": 1
    }
  },
  "message": "Comments retrieved successfully"
}
```

---

## Reverb Channels & Real-Time Events

### Channels List

| Channel Name | Type | Purpose |
|--------------|------|---------|
| `live-sessions` | Public | Emits updates when any astrologer goes live. |
| `live-session.{id}` | Presence | Used for chat comments, viewer counts, and Super Chats in a specific session. |

---

### Events Description

#### 1. `LiveSessionStarted` (Public Channel: `live-sessions`)
*Emitted as soon as an astrologer starts streaming (instant or scheduled). Allows the client app to insert the new ongoing session into the stream list instantly without refreshing.*

##### Event Data Payload:
```json
{
  "id": 13,
  "title": "Instant QA & Reading",
  "astrologer": {
    "id": 5,
    "name": "Priya Sharma",
    "profile_photo": "https://astrogravity.com/storage/photos/abc.jpg"
  },
  "viewer_count": 0,
  "started_at": "2026-06-16T14:06:00.000000Z"
}
```

---

#### 2. `ViewerCountUpdated` (Presence Channel: `live-session.{id}`)
*Emitted in real-time whenever a user joins or leaves the live stream. Keeps the live viewer count badge synchronized.*

##### Event Data Payload:
```json
{
  "live_session_id": 13,
  "viewer_count": 43
}
```

---

#### 3. `NewLiveComment` (Presence Channel: `live-session.{id}`)
*Emitted when a new message is successfully sent to the session chat.*

##### Event Data Payload:
```json
{
  "user_id": 42,
  "user_name": "Rahul K",
  "user_avatar": "https://astrogravity.com/storage/photos/user42.jpg",
  "message": "Amazing session! 🌟",
  "created_at": "2026-06-16T14:20:00.000000Z"
}
```

---

#### 4. `SuperChatReceived` (Presence Channel: `live-session.{id}`)
*Emitted when a Super Chat (paid tip) is successfully sent. Astrologer receives credits, user is debited, and all viewers see the notification overlay.*

##### Event Data Payload:
```json
{
  "user_id": 42,
  "user_name": "Rahul K",
  "user_avatar": "https://astrogravity.com/storage/photos/user42.jpg",
  "amount": 50.00,
  "message": "[Gift: Red Rose] Great reading! 🎉",
  "gift": {
    "id": 3,
    "title": "Red Rose",
    "icon_url": "https://astrogravity.com/storage/gifts/icons/red_rose.png"
  },
  "created_at": "2026-06-16T14:22:00.000000Z"
}
```

---

## Super Chat Transaction Flow

```
User POST /live/{id}/super-chat { gift_id: 3, message: "Great!" }
  │
  ├─► Validate session is 'ongoing'
  ├─► Validate active gift and fetch price
  │
  ├─► DB::beginTransaction()
  │    ├─► Get lock order of wallet IDs: min(user_id, astrologer_user_id) then max(user_id, astrologer_user_id)
  │    ├─► lockForUpdate() first wallet
  │    ├─► lockForUpdate() second wallet
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

---

## Changelog

| Date | Change |
|------|--------|
| 2026-06-12 | Initial live session + super chat implementation |
| 2026-06-16 | Added support for instant live session creation and deterministic deadlock-free locking |
| 2026-06-16 | Added live session started/join real-time events, current ongoing session recovery, and user notification trigger |
