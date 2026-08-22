# 📱 Frontend / Flutter Integration Guide

## 📌 Feature Overview: Prepaid Package Seamless Switching & Zero-Deduction Engine

We have upgraded the backend architecture to handle **Prepaid Package Sessions** and **In-Session Channel Switching (Chat ↔ Call)** with **100% Zero Wallet Deduction guarantee**.

---

## 🔑 Key Architectural Update: Old Flow vs New Flow

### ❌ Old Flow (Do NOT Use For Package Sessions):
Previously, when switching from Chat to Call during an active package:
1. Flutter called `endChat()` → race window created.
2. Flutter called `startCall()` → system sometimes treated it as a new normal session and deducted wallet balance.

### ✅ New Flow (Use This):
Flutter now calls the **Atomic Switch API** in a single call:
1. Call `POST /api/v1/user/packages/session/switch-channel`.
2. The backend safely closes the previous channel (Chat/Call) at `₹0.00` and immediately spawns the new channel at `₹0.00` under the same package timer.

---

## 1. 🔄 New API: Switch Channel (Chat ↔ Call)

Use this API when a user or astrologer wants to switch between Chat and Call during an active package sub-session.

### **Endpoint:**
- **User App:** `POST /api/v1/user/packages/session/switch-channel`
- **Astrologer App:** `POST /api/v1/astrologer/packages/session/switch-channel`

### **Headers:**
```http
Authorization: Bearer <auth_token>
Content-Type: application/json
Accept: application/json
```

---

### **Scenario A: Switching from Chat to Call**

#### **Request Body:**
```json
{
  "sub_session_id": 15,
  "from_channel": "chat",
  "to_channel": "call",
  "offer": "audio"
}
```

#### **Success Response (`200 OK`):**
```json
{
  "success": true,
  "message": "Switched to Call successfully.",
  "data": {
    "action_performed": "switch_channel",
    "from_channel": "chat",
    "to_channel": "call",
    "remaining_seconds": 1680,
    "call_session": {
      "id": 142,
      "consumer_id": 45,
      "provider_id": 12,
      "call_type": "audio",
      "status": "initiated",
      "rate_per_minute": 0
    },
    "sub_session": {
      "id": 15,
      "package_purchase_id": 5,
      "mode": "call",
      "call_session_id": 142,
      "chat_session_id": 88,
      "session_state": "in_progress",
      "call_status": "ringing",
      "chat_status": "closed"
    },
    "banner_data": {
      "sub_session_id": 15,
      "session_state": "in_progress",
      "active_channels": ["call"],
      "remaining_seconds": 1680
    }
  }
}
```

---

### **Scenario B: Switching from Call to Chat**

#### **Request Body:**
```json
{
  "sub_session_id": 15,
  "from_channel": "call",
  "to_channel": "chat",
  "question": "Optional initial question"
}
```

#### **Success Response (`200 OK`):**
```json
{
  "success": true,
  "message": "Switched to Chat successfully.",
  "data": {
    "action_performed": "switch_channel",
    "from_channel": "call",
    "to_channel": "chat",
    "remaining_seconds": 1650,
    "chat_session": {
      "id": 143,
      "consumer_id": 45,
      "provider_id": 12,
      "status": "initiated",
      "rate_per_minute": 0
    },
    "sub_session": {
      "id": 15,
      "package_purchase_id": 5,
      "mode": "chat",
      "call_session_id": 142,
      "chat_session_id": 143,
      "session_state": "in_progress",
      "call_status": "disconnected",
      "chat_status": "active"
    },
    "banner_data": {
      "sub_session_id": 15,
      "session_state": "in_progress",
      "active_channels": ["chat"],
      "remaining_seconds": 1650
    }
  }
}
```

---

## 2. 🔍 Enhanced Current Session APIs (Detect Normal vs Prepaid)

All current session endpoints now return explicit flags so the Flutter app knows immediately whether to display standard wallet rates or the active package timer.

### **Endpoints:**
1. `GET /api/v1/chat/sessions/current`
2. `GET /api/v1/call/current-session`
3. `GET /api/v1/chat/current-session`

### **New Fields in Response:**
- `billing_mode`: `"normal"` or `"prepaid"`
- `is_normal`: `true` if billed per minute from standard wallet
- `is_prepaid`: `true` if active package session (`₹0.00` wallet deduction)
- `is_package_session`: `true` if active package session
- `package_info`: Details of the active package (`package_purchase_id`, `sub_session_id`, `remaining_duration`, etc.)

#### **Sample Response (`GET /api/v1/chat/sessions/current`):**
```json
{
  "success": true,
  "data": {
    "billing_mode": "prepaid",
    "is_normal": false,
    "is_prepaid": true,
    "is_package_session": true,
    "package_info": {
      "package_purchase_id": 5,
      "sub_session_id": 15,
      "package_name": "Combo 30 Mins Chat & Call",
      "total_duration": 1800,
      "remaining_duration": 1680
    },
    "session": {
      "id": 88,
      "consumer_id": 45,
      "provider_id": 12,
      "status": "ongoing",
      "rate_per_minute": 0,
      "billing_mode": "prepaid",
      "is_normal": false,
      "is_prepaid": true,
      "is_package_session": true,
      "package_info": {
        "package_purchase_id": 5,
        "sub_session_id": 15,
        "package_name": "Combo 30 Mins Chat & Call",
        "total_duration": 1800,
        "remaining_duration": 1680
      }
    }
  }
}
```

---

## 3. 🛡️ Safety Guarantees Implemented in Backend

1. **Zero-Rate Database Enforcement:**
   - Any session spawned within a package is strictly saved with `rate_per_minute = 0.00`.
2. **Background Billing Job Guard:**
   - Even if a billing job runs, it detects `PackageSubSession` and immediately aborts wallet debits.
3. **Session Completion Guard:**
   - When calling `endCall` or `endChat`, package sessions compute `finalCost = 0.00` and `chargeAmount = 0.00`.
4. **Heartbeat / Timer Persistence:**
   - Channel switching does not reset or duplicate the package session timer.
