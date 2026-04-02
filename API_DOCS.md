# Astrology WebRTC + Chat API Documentation

This document provides the API specifications for real-time video/audio calling, chat, and presence status.

## General Information
- **Base URL**: `/api/v1`
- **Authentication**: Bearer Token (Laravel Sanctum)
- **Real-time Engine**: Soketi / Pusher (WebSocket)

---

## 🟢 Presence & Status

### 1. Heartbeat Pulse
Clients must send a pulse every 30-60 seconds to stay online.
- **Endpoint**: `POST /presence/pulse`
- **Response**: `200 OK`
- **Effect**: Broadcasts `PresenceUpdated` to `presence-room`.

### 2. Go Offline
Call manually before app logout.
- **Endpoint**: `POST /presence/offline`
- **Response**: `200 OK`
- **Effect**: User marked as offline.

---

## 📞 Video/Audio Calling (WebRTC Signaling)

### 1. Initiate Call
Consumer starts a call and sends the initial SDP Offer.
- **Endpoint**: `POST /call/initiate`
- **Payload**:
```json
{
  "provider_id": 12,
  "offer": "v=0\r\no=jdoe 2890844526 2890842807..."
}
```
- **Response**: `200 OK` returns `session` object.
- **Broadcast**: `CallInitiated` event sent to `user.{provider_id}`.

### 2. Accept Call
Provider accepts and sends SDP Answer.
- **Endpoint**: `POST /call/{sessionId}/accept`
- **Payload**:
```json
{
  "answer": "v=0\r\no=provider..."
}
```
- **Response**: `200 OK`.
- **Effect**: Starts billing ticker. Broadcasts `CallAccepted` to `user.{consumer_id}`.

### 3. Reject Call
- **Endpoint**: `POST /call/{sessionId}/reject`
- **Response**: `200 OK`.
- **Broadcast**: `CallEnded` with `ended_by_id`.

### 4. End Call
- **Endpoint**: `POST /call/{sessionId}/end`
- **Response**: `200 OK` returns final `duration` and `total_cost`.
- **Broadcast**: `CallEnded`.

### 5. Send ICE Candidate
Exchange network info during negotiation.
- **Endpoint**: `POST /call/{sessionId}/ice-candidate`
- **Payload**:
```json
{
  "candidate": "{\"candidate\":\"candidate:0 1 UDP 21221...\"}",
  "receiver_id": 10
}
```
- **Broadcast**: `IceCandidateSent` to `user.{receiver_id}`.

---

## 💬 Real-time Chat

### 1. Initiate Chat
- **Endpoint**: `POST /chat/initiate`
- **Payload**: `{"provider_id": 12}`
- **Response**: `200 OK` with `session_id`.
- **Broadcast**: `ChatInitiated` to provider.

### 2. Accept Chat
- **Endpoint**: `POST /chat/{sessionId}/accept`
- **Response**: `200 OK`.
- **Effect**: Starts billing.

### 3. Send Message
- **Endpoint**: `POST /chat/{sessionId}/message`
- **Payload**:
```json
{
  "receiver_id": 12,
  "message": "Hello!",
  "type": "text"
}
```
- **Broadcast**: `MessageSent` to receiver.

---

## 🔊 WebSocket Events (Channels)

- **User Private Channel**: `private-user.{user_id}`
- **Global Presence Channel**: `presence-presence-room`

### Event Payloads

#### `CallInitiated`
```json
{
  "session": {...},
  "sender": {"id": 1, "name": "John", "profile_photo": "...", "offer": "..."}
}
```

#### `CallAccepted`
```json
{
  "session": {"id": 100, "answer": "...", "status": "ongoing"}
}
```

#### `ChatEnded` / `CallEnded`
```json
{
  "session": {"status": "completed", "total_cost": 45.00},
  "ended_by": 1
}
```

---

## 💰 Billing Logic
- **Minimum Balance**: Users need at least `rate * 5` in wallet to initiate.
- **Tick Tocker**: Backend dispatches a job every minute to deduct funds.
- **Auto-Terminate**: If funds reach 0, backend automatically ends the session and broadcasts `CallEnded` / `ChatEnded`.
