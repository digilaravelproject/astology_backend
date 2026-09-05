# Unified Consultation & Session Lifecycle Specification (Call & Chat: Normal, Prepaid & Live)

This document provides a 100% production-accurate technical specification for the **Unified Consultation Lifecycle** across **Call** and **Chat** sessions in the Astrology Backend. It clearly outlines how the system distinguishes between **Normal (Pay-Per-Minute)**, **Prepaid (Package Duration)**, and **Live (Live Stream Co-host)** consultations, the **Single-Endpoint Architecture** for ending sessions (used identically by both **User** and **Astrologer**), exact JSON request & response structures, WebSocket events, FCM notifications, and database schemas.

---

## 1. Core Architecture & Detection Logic

### 1.1 How the Backend Detects Session Type
When a Call or Chat session is initiated, the backend does **not** rely on indirect hacks or guesses. It explicitly tags the session with a dedicated column:
- **`session_type`**: `enum('normal', 'prepaid', 'live')`

```
                                [SESSION INITIATION]
                                         │
                 ┌───────────────────────┴───────────────────────┐
                 ▼                                               ▼
         Active Package Exists?                          Direct Live Request?
      (package_purchases.status = 'active'             (from live stream room)
      & remaining_duration > 0)                                  │
                 │                                               ▼
       ┌─────────┴─────────┐                           session_type = 'live'
       │                   │                           live_session_id = {id}
       ▼                   ▼
    [YES]                 [NO]
session_type = 'prepaid'  session_type = 'normal'
rate_per_minute = 0.00    rate_per_minute = Astrologer Rate (e.g. ₹25.00)
Wallet Check: BYPASSED    Wallet Check: Min 5 minutes balance required
```

### 1.2 Billing Settlement on Session End (Auto-Resolved)
When a consultation ends, the backend automatically settles billing according to its `session_type`:

| Session Type | Money Deduction (₹) | Duration Deduction (Minutes) | Settlement Engine |
| :--- | :--- | :--- | :--- |
| **`normal`** | Real-time per-minute wallet debit from User (`wallets.balance`) & credit to Astrologer. | 0 minutes from package. Session logged in `call_sessions` / `chat_sessions`. | `WalletService` + `CallBillingTickJob` / `ChatBillingTickJob` |
| **`prepaid`** | **₹0.00** (Wallet is untouched). | Exact seconds consumed (`started_at` to `ended_at`) deducted from `package_purchases.remaining_duration`. | `SessionTimerService` + `PackageSubSession` |
| **`live`** | As per Live Stream rule (Free promotional minutes, special live rate, or Super-Chat priority). | 0 minutes from package. | `LiveKit` SFU room participant teardown |

---

## 2. Endpoints Overview (Dedicated Channels, Shared Endpoints for Roles)

Both **User** and **Astrologer** mobile apps use the **exact same endpoint** to terminate active calls or chats. There is no need for frontend developers to write separate endpoints for User vs Astrologer:

| Action | HTTP Method & Route | Caller Role | Behavior |
| :--- | :--- | :--- | :--- |
| **End Call** | `POST /api/call/{sessionId}/end` | **User** OR **Astrologer** | Disconnects call, settles billing (Wallet or Package), broadcasts `CallEnded` to both sides. |
| **End Chat** | `POST /api/chat/{sessionId}/end` | **User** OR **Astrologer** | Closes chat, settles billing (Wallet or Package), broadcasts `ChatEnded` & `ChatQueueUpdated` to both sides. |

---

## 3. Base Configuration & Global Headers

### Base Details
- **Base URL:** `{BASE_URL}/api`
- **Authentication Scheme:** Laravel Sanctum Bearer Token (`Authorization: Bearer {token}`)

### Standard Headers Required
```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <sanctum_token>
```

---

## 4. Call Lifecycle Endpoints

### 4.1 Initiate Call
- **Method & Route:** `POST /api/call/initiate`
- **Caller:** Consumer (User)
- **Request Payload:**
  ```json
  {
    "provider_id": 45,
    "call_type": "audio" // or "video"
  }
  ```
- **Backend Detection:**
  If the user has an active prepaid package with this astrologer, the backend automatically sets `'session_type': 'prepaid'` and `'rate_per_minute': 0.00`. Otherwise, it validates a minimum 5-minute wallet balance and sets `'session_type': 'normal'`.
- **Response (200 OK):**
  ```json
  {
    "status": "success",
    "message": "Call initiated successfully",
    "data": {
      "session": {
        "id": 108,
        "consumer_id": 12,
        "provider_id": 45,
        "session_type": "prepaid",
        "call_type": "audio",
        "status": "initiated",
        "rate_per_minute": 0.0,
        "created_at": "2026-09-05T17:30:00.000000Z"
      }
    }
  }
  ```

---

### 4.2 Accept Call
- **Method & Route:** `POST /api/call/{sessionId}/accept`
- **Caller:** Provider (Astrologer)
- **Response (200 OK):**
  ```json
  {
    "status": "success",
    "message": "Call accepted successfully",
    "data": {
      "session": {
        "id": 108,
        "session_type": "prepaid",
        "status": "ongoing",
        "started_at": "2026-09-05T17:30:15.000000Z"
      }
    }
  }
  ```

---

### 4.3 End Call (Unified Endpoint for User & Astrologer)
- **Method & Route:** `POST /api/call/{sessionId}/end`
- **Caller:** **User** OR **Astrologer** (Whichever hangs up first)
- **Request Body:** Empty `{}` (or optional `{ "reason": "user_hangup" }`)

#### Response A: When Ending a Normal Call (Pay-Per-Minute Wallet)
```json
{
  "status": "success",
  "message": "Call ended successfully",
  "data": {
    "session": {
      "id": 108,
      "consumer_id": 12,
      "provider_id": 45,
      "session_type": "normal",
      "call_type": "audio",
      "status": "completed",
      "started_at": "2026-09-05T17:30:15.000000Z",
      "ended_at": "2026-09-05T17:35:15.000000Z",
      "duration_seconds": 300,
      "rate_per_minute": 25.0,
      "total_cost": 125.0
    },
    "billing": {
      "session_type": "normal",
      "duration_seconds": 300,
      "package_remaining_seconds": null,
      "user_details": {
        "duration_seconds": 300,
        "amount_deducted": 125.0
      },
      "astrologer_details": {
        "duration_seconds": 300,
        "amount_added": 100.0
      }
    }
  }
}
```

#### Response B: When Ending a Prepaid Package Call
```json
{
  "status": "success",
  "message": "Call ended successfully",
  "data": {
    "session": {
      "id": 108,
      "consumer_id": 12,
      "provider_id": 45,
      "session_type": "prepaid",
      "call_type": "audio",
      "status": "completed",
      "started_at": "2026-09-05T17:30:15.000000Z",
      "ended_at": "2026-09-05T17:35:15.000000Z",
      "duration_seconds": 300,
      "rate_per_minute": 0.0,
      "total_cost": 0.0
    },
    "billing": {
      "session_type": "prepaid",
      "duration_seconds": 300,
      "package_remaining_seconds": 1500,
      "user_details": {
        "duration_seconds": 300,
        "amount_deducted": 0.0
      },
      "astrologer_details": {
        "duration_seconds": 300,
        "amount_added": 0.0
      }
    }
  }
}
```

---

## 5. Chat Lifecycle Endpoints

### 5.1 Initiate Chat
- **Method & Route:** `POST /api/chat/initiate`
- **Caller:** Consumer (User)
- **Request Payload:**
  ```json
  {
    "provider_id": 45,
    "question": "What does my career chart say for this year?"
  }
  ```
- **Response (200 OK):**
  ```json
  {
    "status": "success",
    "message": "Chat initiated successfully",
    "data": {
      "session": {
        "id": 204,
        "consumer_id": 12,
        "provider_id": 45,
        "session_type": "normal",
        "question": "What does my career chart say for this year?",
        "status": "initiated",
        "rate_per_minute": 20.0
      }
    }
  }
  ```

---

### 5.2 Accept Chat
- **Method & Route:** `POST /api/chat/{sessionId}/accept`
- **Caller:** Provider (Astrologer)
- **Response (200 OK):**
  ```json
  {
    "status": "success",
    "message": "Chat accepted successfully",
    "data": {
      "session": {
        "id": 204,
        "session_type": "normal",
        "status": "ongoing",
        "started_at": "2026-09-05T17:31:00.000000Z"
      },
      "default_message": {
        "id": 501,
        "message": "Namaste! How can I help you today?",
        "type": "text"
      }
    }
  }
  ```

---

### 5.3 End Chat (Unified Endpoint for User & Astrologer)
- **Method & Route:** `POST /api/chat/{sessionId}/end`
- **Caller:** **User** OR **Astrologer**
- **Request Body:** Empty `{}`

#### Response A: When Ending a Normal Chat (Pay-Per-Minute Wallet)
```json
{
  "status": "success",
  "message": "Chat ended successfully",
  "data": {
    "session": {
      "id": 204,
      "session_type": "normal",
      "status": "completed",
      "duration_seconds": 420,
      "rate_per_minute": 20.0,
      "total_cost": 140.0
    },
    "billing": {
      "session_type": "normal",
      "duration_seconds": 420,
      "package_remaining_seconds": null,
      "user_details": {
        "duration_seconds": 420,
        "amount_deducted": 140.0
      },
      "astrologer_details": {
        "duration_seconds": 420,
        "amount_added": 112.0
      }
    }
  }
}
```

#### Response B: When Ending a Prepaid Package Chat
```json
{
  "status": "success",
  "message": "Chat ended successfully",
  "data": {
    "session": {
      "id": 204,
      "session_type": "prepaid",
      "status": "completed",
      "duration_seconds": 420,
      "rate_per_minute": 0.0,
      "total_cost": 0.0
    },
    "billing": {
      "session_type": "prepaid",
      "duration_seconds": 420,
      "package_remaining_seconds": 1380,
      "user_details": {
        "duration_seconds": 420,
        "amount_deducted": 0.0
      },
      "astrologer_details": {
        "duration_seconds": 420,
        "amount_added": 0.0
      }
    }
  }
}
```

---

## 6. Real-Time WebSocket Broadcast Events

Broadcasted via Laravel WebSockets (Reverb / Pusher). Mobile apps should subscribe to `private-user.{userId}` and `private-call.{sessionId}` or `private-chat.{sessionId}`.

### 6.1 `CallInitiated`
- **Channel:** `private-user.{providerId}`, `private-call.{sessionId}`
- **Trigger:** When user initiates a call.
- **Payload:**
  ```json
  {
    "session": {
      "id": 108,
      "consumer_id": 12,
      "provider_id": 45,
      "session_type": "prepaid",
      "call_type": "audio",
      "status": "initiated",
      "rate_per_minute": 0.0
    },
    "callerData": {
      "id": 12,
      "name": "Rahul Verma",
      "phone": "+919876543210",
      "gender": "male",
      "date_of_birth": "1995-08-15T00:00:00.000Z",
      "time_of_birth": "14:30",
      "place_of_birth": "New Delhi, India",
      "profile_photo_url": "https://cdn.example.com/profiles/12.jpg"
    }
  }
  ```

---

### 6.2 `ChatInitiated`
- **Channel:** `private-user.{providerId}`, `private-chat.{sessionId}`
- **Trigger:** When user initiates a chat.
- **Payload:**
  ```json
  {
    "session": {
      "id": 204,
      "consumer_id": 12,
      "provider_id": 45,
      "session_type": "normal",
      "question": "Need guidance regarding marriage.",
      "status": "initiated",
      "rate_per_minute": 20.0
    },
    "senderData": {
      "id": 12,
      "name": "Rahul Verma",
      "gender": "male",
      "date_of_birth": "1995-08-15T00:00:00.000Z"
    }
  }
  ```

---

### 6.3 `CallEnded` & `ChatEnded`
- **Channel:** `private-user.{consumerId}`, `private-user.{providerId}`, `private-call.{sessionId}` / `private-chat.{sessionId}`
- **Trigger:** Fired immediately when either participant calls the end endpoint.
- **Payload:**
  ```json
  {
    "session": {
      "id": 108,
      "session_type": "prepaid",
      "status": "completed",
      "duration_seconds": 300,
      "total_cost": 0.0
    },
    "ended_by_id": 12,
    "ended_by_role": "user",
    "billing": {
      "session_type": "prepaid",
      "duration_seconds": 300,
      "user_details": {
        "duration_seconds": 300,
        "amount_deducted": 0.0
      },
      "astrologer_details": {
        "duration_seconds": 300,
        "amount_added": 0.0
      }
    }
  }
  ```

---

### 6.4 `CallDismissed` & `ChatDismissed`
- **Channel:** `private-user.{consumerId}`, `private-user.{providerId}`
- **Trigger:** When rejected, cancelled, or when ringing times out (60s).
- **Payload:**
  ```json
  {
    "session_id": 108,
    "reason": "rejected", // or "cancelled" or "timeout"
    "dismissed_by_id": 45,
    "dismissed_by_role": "astrologer"
  }
  ```

---

## 7. FCM Push & In-App Notification Delivery

All events trigger queued listeners that deliver high-priority FCM notifications and insert into the `app_notifications` table:

| Event | Listener | Push Title | Push Body Sample |
| :--- | :--- | :--- | :--- |
| **Call Initiated** | `SendCallPushNotificationListener` | Incoming Call 📞 | "Incoming Audio Call from Rahul Verma (`prepaid`)" |
| **Chat Initiated** | `SendChatInitiatedPushListener` | New Chat Request 💬 | "Rahul Verma has requested a chat consultation." |
| **Session Accepted** | `SendSessionAcceptedPushListener` | Request Accepted ✅ | "Acharya Sharma accepted your consultation." |
| **Normal Session Ended** | `SendSessionEndedPushListener` | Consultation Ended ✨ | "Your Call ended. Duration: 05:00. Deducted: ₹125.00. Balance: ₹375.00." |
| **Prepaid Session Ended** | `SendSessionEndedPushListener` | Consultation Ended ✨ | "Your Call ended. Duration: 05:00. Package minutes updated." |
| **Session Declined** | `SendSessionDismissedPushListener` | Request Declined ❌ | "Astrologer is currently busy / declined your request." |

---

## 8. Database Architecture Reference

```
┌────────────────────────────────────────────────────────────────────────┐
│                              USERS TABLE                               │
│  id, name, phone, user_type ('user', 'astrologer'), profile_photo ... │
└──────────────────┬──────────────────────────────────┬──────────────────┘
                   │                                  │
                   ▼                                  ▼
   ┌───────────────────────────────┐  ┌──────────────────────────────────┐
   │       PACKAGE_PURCHASES       │  │          WALLETS TABLE           │
   │  id, user_id, astrologer_id   │  │  id, user_id, balance            │
   │  total_duration (sec)         │  └──────────────────────────────────┘
   │  remaining_duration (sec)     │
   │  purchase_price, status       │
   └───────────────┬───────────────┘
                   │
                   ▼
   ┌───────────────────────────────┐
   │     PACKAGE_SUB_SESSIONS      │
   │  id, package_purchase_id      │
   │  chat_session_id (nullable)   │
   │  call_session_id (nullable)   │
   │  duration_used, session_state │
   └───────────────┬───────────────┘
                   │
         ┌─────────┴─────────┐
         ▼                   ▼
┌─────────────────────────┐ ┌─────────────────────────┐
│      CALL_SESSIONS      │ │      CHAT_SESSIONS      │
│  id, consumer_id        │ │  id, consumer_id        │
│  provider_id            │ │  provider_id            │
│  session_type (enum)    │ │  session_type (enum)    │
│  ('normal','prepaid',   │ │  ('normal','prepaid')   │
│   'live')               │ │  status, question       │
│  live_session_id        │ │  rate_per_minute        │
│  call_type, status      │ │  duration_seconds       │
│  rate_per_minute        │ │  total_cost             │
│  duration_seconds       │ │  started_at, ended_at   │
│  total_cost             │ └─────────────────────────┘
│  started_at, ended_at   │
└─────────────────────────┘
```

---

## 9. Quick Guide for Flutter Mobile Developers

1. **Check Consultation Type on Incoming Screens**:
   Use `event.session.session_type`:
   - If `'prepaid'`: Show badge **"Prepaid Package Call"**.
   - If `'normal'`: Show badge **"Pay-Per-Minute (₹{rate}/min)"**.
   - If `'live'`: Show badge **"Live Stream Co-Host Call"**.

2. **Ending Any Call or Chat**:
   Call the exact same API regardless of whether user or astrologer pressed end:
   - For Calls: `POST /api/call/{sessionId}/end`
   - For Chats: `POST /api/chat/{sessionId}/end`

3. **Showing Summary Screen**:
   Inspect `response.data.billing`:
   - If `billing.session_type == 'prepaid'`:
     Show: *"Package Time Deducted: {billing.duration_seconds}s | Remaining: {billing.package_remaining_seconds}s"*. (Do not show ₹ deduction).
   - If `billing.session_type == 'normal'`:
     Show: *"Total Paid: ₹{billing.user_details.amount_deducted} | Duration: {billing.duration_seconds}s"*.
