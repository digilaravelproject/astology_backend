# Push Notification Sound & Channel Specification

> **Audience:** Mobile App Developers (Flutter / iOS / Android) & Frontend Engineers  
> **Backend Protocol:** Firebase Cloud Messaging (FCM) HTTP v1 API  
> **Last Updated:** August 2026

---

## 1. Overview

The backend now features an **Admin-Controlled Notification Sound System**. This prevents repetitive, annoying sound alerts during active chat conversations while ensuring critical alerts (like incoming calls, new chat session requests, and live streams) remain audible.

---

## 2. Notification Sound & Channel Behavior Matrix

| Notification `type` | Description | Default Sound | Android Channel ID | APNs Sound (iOS) | `data.play_sound` |
| :--- | :--- | :---: | :---: | :---: | :---: |
| **`chat`** | Regular ongoing message in an active session | **Silent** (`0`) | `chat_channel` | *Omitted (Silent Banner)* | `"0"` |
| **`session_request`** / **`CHAT_REQUEST`** | Astrologer receives a new chat request | **Sound** (`1`) | `chat_channel` | `default` | `"1"` |
| **`call`** / **`CALL_REQUEST`** | Incoming Audio/Video Call wake-up alert | **Ringtone** (`1`) | `call_channel` | `default` / `call_ringtone` | `"1"` |
| **`live_stream`** / **`live`** | Astrologer starts a live stream broadcast | **Sound** (`1`) | `live_session_channel` | `default` | `"1"` |
| **`promo`** / **`system`** | General announcements & wallet updates | **Sound** (`1`) | `astology_notifications` | `default` | `"1"` |

---

## 3. FCM Payload Structure

Every push notification sent by the backend contains both standard OS notification headers and a comprehensive `data` map.

### Example: Regular Chat Message (Silent)
```json
{
  "notification": {
    "title": "Rahul Sharma",
    "body": "Hello, can you check my chart?"
  },
  "data": {
    "type": "chat",
    "session_id": "452",
    "sender_id": "18",
    "sender_name": "Rahul Sharma",
    "click_action": "FLUTTER_NOTIFICATION_CLICK",
    "play_sound": "0",
    "sound": "",
    "created_at": "2026-08-22T12:00:00+05:30"
  },
  "android": {
    "priority": "HIGH",
    "notification": {
      "channel_id": "chat_channel",
      "notification_priority": "PRIORITY_HIGH",
      "default_vibrate_timings": false,
      "visibility": "PUBLIC",
      "click_action": "FLUTTER_NOTIFICATION_CLICK"
    }
  },
  "apns": {
    "payload": {
      "aps": {
        "content-available": 1,
        "badge": 1
      }
    }
  }
}
```

---

### Example: New Chat Request / Initiate (Audible)
```json
{
  "notification": {
    "title": "New Chat Request 💬",
    "body": "Priya Verma has requested a chat consultation with you."
  },
  "data": {
    "type": "CHAT_REQUEST",
    "session_id": "452",
    "user_id": "89",
    "user_name": "Priya Verma",
    "channel_type": "chat",
    "screen_route": "/chat-request",
    "click_action": "FLUTTER_NOTIFICATION_CLICK",
    "play_sound": "1",
    "sound": "default",
    "created_at": "2026-08-22T12:00:00+05:30"
  },
  "android": {
    "priority": "HIGH",
    "notification": {
      "channel_id": "chat_channel",
      "default_sound": true,
      "default_vibrate_timings": true,
      "notification_priority": "PRIORITY_HIGH"
    }
  },
  "apns": {
    "payload": {
      "aps": {
        "sound": "default",
        "content-available": 1,
        "badge": 1
      }
    }
  }
}
```

---

### Example: Live Stream Broadcast (Audible + Deep-Linking)
```json
{
  "notification": {
    "title": "🔴 Acharya Vivek is LIVE now!",
    "body": "Join live session 'Daily Horoscope & Career Prediction' now!"
  },
  "data": {
    "type": "live_stream",
    "screen": "LIVE_STREAM_SCREEN",
    "screen_route": "/live-stream",
    "route": "live_session",
    "session_id": "130",
    "live_session_id": "130",
    "channel_name": "live_130",
    "room_uuid": "live_130",
    "astrologer_id": "5",
    "astrologer_name": "Acharya Vivek",
    "astrologer_avatar": "https://example.com/uploads/astrologers/vivek.jpg",
    "play_sound": "1",
    "sound": "default",
    "click_action": "FLUTTER_NOTIFICATION_CLICK"
  },
  "android": {
    "notification": {
      "channel_id": "live_session_channel",
      "default_sound": true
    }
  }
}
```

---

## 4. Mobile Client Implementation Guidelines

### A. Android Notification Channels
Create the following notification channels in your app initialization:

1. **`live_session_channel`**
   - **Name:** "Live Session Alerts"
   - **Importance:** `Importance.high`
   - **Sound:** Enabled

2. **`chat_channel`**
   - **Name:** "Chat Messages & Requests"
   - **Importance:** `Importance.high`
   - **Sound:** Enabled (Channel allows sound, but individual silent messages control sound via payload)

3. **`call_channel`**
   - **Name:** "Incoming Calls"
   - **Importance:** `Importance.max`
   - **Sound:** Custom ringtone or default call sound

4. **`astology_notifications`**
   - **Name:** "General Announcements"
   - **Importance:** `Importance.default`

---

### B. Foreground Notification Handling (`onMessage`)
When the app is open on the user's screen:

1. Read `message.data['play_sound']`:
   - If `play_sound == '0'` or `play_sound == 'false'`:
     - Show in-app notification banner or update unread badge **WITHOUT** playing local audio chimes.
   - If `play_sound == '1'` or `play_sound == 'true'`:
     - Trigger standard local audio chime / vibrate.

2. If the user is **currently viewing the active chat screen** (`activeSessionId == message.data['session_id']`):
   - Suppress the top notification popup entirely (since the message is already appended to the chat UI in real-time via WebSocket).

---

### C. Background & Killed State
- Android & iOS OS Notification Managers will automatically honor the `sound` and `default_sound` tags provided in the FCM payload. No extra client logic is required for background delivery.

---

## 5. Summary of Data Keys for Quick Reference

| Key | Type | Description |
| :--- | :---: | :--- |
| `type` | `String` | `'chat'`, `'session_request'`, `'call'`, `'live_stream'`, `'promo'` |
| `play_sound` | `String` | `'1'` (Audible) or `'0'` (Silent) |
| `sound` | `String` | `'default'`, `'call_ringtone'`, or `''` |
| `screen` / `screen_route` | `String` | Deep link target (`'LIVE_STREAM_SCREEN'`, `'/live-stream'`, `'/chat-request'`) |
| `session_id` | `String` | ID of the Chat, Call, or Live Session |
| `click_action` | `String` | `'FLUTTER_NOTIFICATION_CLICK'` |
