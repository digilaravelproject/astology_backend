# Package Session API — Flutter Integration Spec

**Version**: 2.1 *(Updated after Chat/Call integration and unit updates)*
**Base URL**: `https://your-domain.com/api/v1`
**Authentication**: Bearer Token via `Authorization: Bearer <token>`
**Content-Type**: `application/json`

---

## Overview

The Prepaid Package Session system allows a **user (consumer)** to purchase a fixed time block with a specific **astrologer**, then consume that time across multiple sequential sub-sessions of type `chat` or `call`. Billing is purely prepaid — **no per-minute wallet tick** occurs during an active package session.

```
User purchases package
  → POST /packages/session/start (mode=chat)
      → Creates real ChatSession internally
      → Astrologer gets existing "chat_initiated" WebSocket notification
      → Astrologer calls existing POST /chat/{id}/accept
      → Chat messages work via existing endpoints (no billing tick)
  → POST /packages/session/end
      → Deducts elapsed duration from package
      → Ends the linked ChatSession automatically
  → POST /packages/session/start (mode=call)
      → Creates real CallSession internally
      → Astrologer calls existing POST /call/{id}/accept (Agora token issued)
      → Call runs with no billing tick
  → (repeat until package exhausted)
```

> **Important for Flutter**: After `POST /packages/session/start`, the response contains **both** the `sub_session` record AND the actual `chat_session` or `call_session`. Use the `chat_session.id` / `call_session.id` for all subsequent chat message / call signaling requests via the existing endpoints.

### Key Concepts

| Concept | Description |
|---|---|
| `PackagePurchase` | Upfront purchase binding user ↔ astrologer with total/remaining duration |
| `PackageSubSession` | Timer record for one chat or call run under the package |
| `ChatSession` / `CallSession` | Real chat/call session — created internally when sub-session starts |
| `remaining_duration` | Time left in the package. Updated on every sub-session end |
| `status: exhausted` | Package fully consumed. No further sub-sessions allowed |

---

## 1. Purchase a Package

### `POST /packages/purchase`

Purchase a prepaid session package from a specific astrologer. Funds are deducted from the user's wallet immediately and the astrologer receives their commission share.

**Request Body**
```json
{
  "astrologer_id": 42
}
```

**Success `201`**
```json
{
  "success": true,
  "message": "Package purchased successfully.",
  "data": {
    "purchase": {
      "id": 17,
      "user_id": 5,
      "astrologer_id": 42,
      "total_duration": 3600,
      "remaining_duration": 3600,
      "purchase_price": "150.00",
      "commission_percentage": "50.00",
      "admin_earnings": "75.00",
      "astrologer_earnings": "75.00",
      "status": "active",
      "created_at": "2026-07-16T10:30:00.000000Z",
      "updated_at": "2026-07-16T10:30:00.000000Z"
    }
  }
}
```

**Error Codes**

| `error_code` | HTTP | Cause |
|---|---|---|
| `INSUFFICIENT_BALANCE` | 422 | User wallet balance < package price |
| `PACKAGE_PURCHASE_FAILED` | 422 | General transaction failure |
| `SERVER_ERROR` | 500 | Unexpected failure |

**Error Response Shape**
```json
{
  "success": false,
  "error_code": "INSUFFICIENT_BALANCE",
  "message": "Insufficient balance. Please recharge your wallet.",
  "tracking_uuid": "c4f712b2-88a1-4bce-9e77-123456789abc"
}
```

---

## 2. Check Active Package Status

### `GET /packages/active-status?astrologer_id=42`

Fetch the active package purchase and currently running sub-session for a user ↔ astrologer pair. Call this on screen load to restore UI state after app kill/disconnect.

**Query Parameters**

| Param | Type | Required | Description |
|---|---|---|---|
| `astrologer_id` | integer | Yes | The astrologer's user ID |

**Success `200` — Active package with running sub-session**
```json
{
  "success": true,
  "data": {
    "has_active_package": true,
    "package_purchase": {
      "id": 17,
      "user_id": 5,
      "astrologer_id": 42,
      "total_duration": 3600,
      "remaining_duration": 2100,
      "purchase_price": "150.00",
      "status": "active"
    },
    "active_sub_session": {
      "id": 3,
      "package_purchase_id": 17,
      "mode": "chat",
      "chat_session_id": 88,
      "call_session_id": null,
      "started_at": "2026-07-16T11:00:00.000000Z",
      "ended_at": null,
      "duration_used": 0
    }
  }
}
```

**When no active package exists:**
```json
{
  "success": true,
  "data": {
    "has_active_package": false,
    "package_purchase": null,
    "active_sub_session": null
  }
}
```

---

## 3. Start a Sub-Session

### `POST /packages/session/start`

Starts a chat or call sub-session under an active package purchase.

**What this does internally:**
- Creates a `PackageSubSession` record (timer tracking)
- Calls `ChatService::initiateChat()` or `CallService::initiateCall()` internally (wallet balance check bypassed for package users)
- Astrologer receives the **existing** chat/call WebSocket notification — **no change needed on astrologer side**
- Dispatches a background timer job to force-terminate after `remaining_duration` seconds
- Broadcasts `PackageSubSessionStarted` WebSocket event for the countdown timer

**Request Body**
```json
{
  "astrologer_id": 42,
  "mode": "chat",
  "question": "What does my 2026 look like?"
}
```

| Field | Type | Required | Description |
|---|---|---|---|
| `astrologer_id` | integer | ✅ Yes | The astrologer's user ID |
| `mode` | string | ✅ Yes | `chat` or `call` |
| `question` | string | ❌ Optional | Initial question (chat mode only) |

**Success `200` — Chat mode**
```json
{
  "success": true,
  "message": "Package sub-session started successfully.",
  "data": {
    "sub_session": {
      "id": 4,
      "package_purchase_id": 17,
      "mode": "chat",
      "chat_session_id": 88,
      "call_session_id": null,
      "started_at": "2026-07-16T11:25:00.000000Z",
      "ended_at": null,
      "duration_used": 0
    },
    "remaining_duration": 2100,
    "chat_session": {
      "id": 88,
      "consumer_id": 5,
      "provider_id": 42,
      "status": "initiated",
      "rate_per_minute": "0.00",
      "question": "What does my 2026 look like?",
      "created_at": "2026-07-16T11:25:00.000000Z"
    }
  }
}
```

**Success `200` — Call mode**
```json
{
  "success": true,
  "message": "Package sub-session started successfully.",
  "data": {
    "sub_session": {
      "id": 5,
      "package_purchase_id": 17,
      "mode": "call",
      "chat_session_id": null,
      "call_session_id": 31,
      "started_at": "2026-07-16T12:00:00.000000Z",
      "ended_at": null,
      "duration_used": 0
    },
    "remaining_duration": 900,
    "call_session": {
      "id": 31,
      "consumer_id": 5,
      "provider_id": 42,
      "status": "initiated",
      "call_type": "audio",
      "rate_per_minute": "0.00",
      "created_at": "2026-07-16T12:00:00.000000Z"
    }
  }
}
```

> **Flutter usage**: Use `chat_session.id` with existing `/chat/{id}/message`, `/chat/{id}/read` endpoints. Use `call_session.id` for Agora token acquisition after astrologer accepts.

**Error Codes**

| `error_code` | HTTP | Cause |
|---|---|---|
| `PACKAGE_SESSION_START_FAILED` | 422 | No active package, already running, astrologer busy, etc. |

---

## 4. End a Sub-Session

### `POST /packages/session/end`

Terminates the active sub-session. Deducts actual elapsed seconds from `remaining_duration`. **Automatically ends the linked `ChatSession` or `CallSession`** via existing service logic.

**Request Body**
```json
{
  "sub_session_id": 4
}
```

**Success `200`**
```json
{
  "success": true,
  "message": "Package sub-session ended successfully.",
  "data": {
    "sub_session": {
      "id": 4,
      "package_purchase_id": 17,
      "mode": "chat",
      "chat_session_id": 88,
      "started_at": "2026-07-16T11:25:00.000000Z",
      "ended_at": "2026-07-16T11:45:00.000000Z",
      "duration_used": 1200
    },
    "remaining_duration": 900
  }
}
```

**When package is now exhausted:**
```json
{
  "success": true,
  "message": "Package sub-session ended successfully.",
  "data": {
    "sub_session": { "...": "..." },
    "remaining_duration": 0
  }
}
```
*(Server also broadcasts `PackageSessionTerminated` WebSocket event when exhausted)*

**Error Codes**

| `error_code` | HTTP | Cause |
|---|---|---|
| `PACKAGE_SESSION_END_FAILED` | 422 | Sub-session not found or unauthorized |

---

## 5. Real-Time WebSocket Events

All **package-specific** events broadcast on the private channels of both participants:

```
private-user.{consumerId}
private-user.{astrologerId}
```

> **Note**: Existing chat events (`ChatMessage`, `ChatAccepted`, etc.) and call events (`CallAccepted`, etc.) continue to fire as normal via their own channels. Package events are **additional** and carry countdown/timer data.

### `PackageSubSessionStarted`

Fired when sub-session starts. Start your local countdown timer.

```json
{
  "event": "App\\Events\\PackageSubSessionStarted",
  "data": {
    "sub_session_id": 4,
    "package_purchase_id": 17,
    "mode": "chat",
    "remaining_duration": 2100,
    "started_at": "2026-07-16T11:25:00.000000Z"
  },
  "channel": "private-user.5"
}
```

### `PackageSubSessionEnded`

Fired when sub-session ends (user-initiated). Update countdown UI.

```json
{
  "event": "App\\Events\\PackageSubSessionEnded",
  "data": {
    "sub_session_id": 4,
    "package_purchase_id": 17,
    "mode": "chat",
    "duration_used": 1200,
    "remaining_duration": 900
  },
  "channel": "private-user.5"
}
```

### `PackageSessionTerminated` ⚠️ Critical

Fired by the background timer job when time runs out, OR when the last sub-session exhausts the package. Flutter **must** handle this immediately:

1. Close the chat feed / disconnect from Agora
2. Show an expiry dialog
3. Navigate user away from session screen

```json
{
  "event": "App\\Events\\PackageSessionTerminated",
  "data": {
    "package_purchase_id": 17,
    "mode": "call",
    "message": "Your package session was forcefully terminated due to time expiration.",
    "remaining_duration": 0,
    "package_status": "exhausted"
  },
  "channel": "private-user.5"
}
```

---

## 6. Flutter Implementation

### 6.1 Purchase Package

```dart
Future<PackagePurchase> purchasePackage({required int astrologerId}) async {
  try {
    final response = await apiClient.post(
      '/packages/purchase',
      data: {'astrologer_id': astrologerId},
    );
    return PackagePurchase.fromJson(response.data['data']['purchase']);
  } on DioException catch (e) {
    final errorCode = e.response?.data['error_code'] as String? ?? 'SERVER_ERROR';
    final message = e.response?.data['message'] as String? ?? 'Unexpected error.';
    throw PackageException(code: errorCode, message: message);
  }
}
```

### 6.2 Start Sub-Session

Returns both `PackageSubSession` and the linked real session. Use `chatSession` to start sending messages, use `callSession.id` with your Agora token request.

```dart
class StartSubSessionResult {
  final PackageSubSession subSession;
  final int remainingDuration;
  final ChatSession? chatSession;   // non-null when mode == 'chat'
  final CallSession? callSession;   // non-null when mode == 'call'

  StartSubSessionResult.fromJson(Map<String, dynamic> json)
      : subSession = PackageSubSession.fromJson(json['sub_session']),
        remainingDuration = json['remaining_duration'],
        chatSession = json['chat_session'] != null
            ? ChatSession.fromJson(json['chat_session'])
            : null,
        callSession = json['call_session'] != null
            ? CallSession.fromJson(json['call_session'])
            : null;
}

Future<StartSubSessionResult> startSubSession({
  required int astrologerId,
  required String mode, // 'chat' or 'call'
  String? question,
}) async {
  final response = await apiClient.post(
    '/packages/session/start',
    data: {
      'astrologer_id': astrologerId,
      'mode': mode,
      if (question != null) 'question': question,
    },
  );
  return StartSubSessionResult.fromJson(response.data['data']);
}
```

### 6.3 End Sub-Session

```dart
Future<void> endSubSession({required int subSessionId}) async {
  await apiClient.post(
    '/packages/session/end',
    data: {'sub_session_id': subSessionId},
  );
}
```

### 6.4 WebSocket Listeners

```dart
class PackageSessionListener {
  final EchoChannel _channel;

  PackageSessionListener({required int userId, required Echo echo})
      : _channel = echo.private('user.$userId');

  void listen({
    required void Function(Map data) onStarted,
    required void Function(Map data) onEnded,
    required void Function(Map data) onTerminated,
  }) {
    _channel
      .listen('PackageSubSessionStarted', onStarted)
      .listen('PackageSubSessionEnded', onEnded)
      .listen('PackageSessionTerminated', (data) {
        // CRITICAL: immediately close chat/call UI
        onTerminated(data);
      });
  }

  void dispose() {
    _channel
      ..stopListening('PackageSubSessionStarted')
      ..stopListening('PackageSubSessionEnded')
      ..stopListening('PackageSessionTerminated');
  }
}
```

### 6.5 Countdown Timer Widget

Shows minutes remaining in the UI but computes based on seconds internally.

```dart
class PackageTimerWidget extends StatefulWidget {
  final int remainingSeconds;
  const PackageTimerWidget({super.key, required this.remainingSeconds});

  @override
  State<PackageTimerWidget> createState() => _PackageTimerWidgetState();
}

class _PackageTimerWidgetState extends State<PackageTimerWidget> {
  late int _seconds;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _seconds = widget.remainingSeconds;
    _timer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (!mounted) return;
      if (_seconds > 0) setState(() => _seconds--);
      else _timer?.cancel();
    });
  }

  /// Call this immediately on PackageSessionTerminated event
  void forceStop() {
    _timer?.cancel();
    if (mounted) setState(() => _seconds = 0);
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  String get _formatted {
    final m = _seconds ~/ 60;
    final s = _seconds % 60;
    return '${m.toString().padLeft(2, '0')}:${s.toString().padLeft(2, '0')} mins';
  }

  @override
  Widget build(BuildContext context) => Text(
    _formatted,
    style: TextStyle(
      fontSize: 24,
      fontWeight: FontWeight.bold,
      color: _seconds < 60 ? Colors.red : Colors.green,
    ),
  );
}
```

---

## 7. Data Models (Dart)

```dart
class PackagePurchase {
  final int id;
  final int userId;
  final int astrologerId;
  final int totalDuration; // total seconds (API)
  final int remainingDuration; // remaining seconds (API)
  final double purchasePrice;
  final String status; // 'active' | 'exhausted'
  final DateTime createdAt;

  PackagePurchase.fromJson(Map<String, dynamic> json)
      : id = json['id'],
        userId = json['user_id'],
        astrologerId = json['astrologer_id'],
        totalDuration = json['total_duration'],
        remainingDuration = json['remaining_duration'],
        purchasePrice = double.parse(json['purchase_price'].toString()),
        status = json['status'],
        createdAt = DateTime.parse(json['created_at']);
}

class PackageSubSession {
  final int id;
  final int packagePurchaseId;
  final String mode;        // 'chat' | 'call'
  final int? chatSessionId; // linked ChatSession.id — use for chat API calls
  final int? callSessionId; // linked CallSession.id — use for call/Agora token
  final DateTime startedAt;
  final DateTime? endedAt;
  final int durationUsed; // seconds used (API)

  PackageSubSession.fromJson(Map<String, dynamic> json)
      : id = json['id'],
        packagePurchaseId = json['package_purchase_id'],
        mode = json['mode'],
        chatSessionId = json['chat_session_id'],
        callSessionId = json['call_session_id'],
        startedAt = DateTime.parse(json['started_at']),
        endedAt = json['ended_at'] != null ? DateTime.parse(json['ended_at']) : null,
        durationUsed = json['duration_used'] ?? 0;
}

class ChatSession {
  final int id;
  final int consumerId;
  final int providerId;
  final String status;

  ChatSession.fromJson(Map<String, dynamic> json)
      : id = json['id'],
        consumerId = json['consumer_id'],
        providerId = json['provider_id'],
        status = json['status'];
}

class CallSession {
  final int id;
  final int consumerId;
  final int providerId;
  final String status;
  final String callType;

  CallSession.fromJson(Map<String, dynamic> json)
      : id = json['id'],
        consumerId = json['consumer_id'],
        providerId = json['provider_id'],
        status = json['status'],
        callType = json['call_type'] ?? 'audio';
}
```

---

## 8. Complete Session Lifecycle

```
[IDLE]
  │
  ├─ POST /packages/purchase
  │
  ▼
[PACKAGE ACTIVE — remaining_duration: 3600s / 60 mins]
  │
  ├─ POST /packages/session/start { mode: "chat", question: "..." }
  │     → Creates PackageSubSession (timer starts)
  │     → Creates ChatSession internally (status: initiated)
  │     → Astrologer receives existing "chat_initiated" WS event
  │     → Response includes chat_session.id
  │
  ▼
[WAITING FOR ASTROLOGER ACCEPT]
  │
  ├─ Astrologer: POST /chat/{chat_session_id}/accept   ← EXISTING ENDPOINT, NO CHANGE
  │     → ChatSession status = ongoing
  │     → No billing tick (package active — bypassed)
  │
  ▼
[CHAT ONGOING] ──── WS: PackageSubSessionStarted (countdown begins)
  │
  │  Chat messages via existing: POST /chat/{id}/message
  │  Read receipts via existing: POST /chat/{id}/read
  │
  ├─ POST /packages/session/end { sub_session_id: 4 }
  │     → Deducts elapsed duration from remaining_duration
  │     → Auto-calls ChatService::endChat() internally
  │
  ▼
[CHAT ENDED] ──── WS: PackageSubSessionEnded (remaining: 2400s / 40 mins)
  │
  ├─ POST /packages/session/start { mode: "call" }
  │     → Creates CallSession internally (status: initiated)
  │     → Astrologer receives existing "call_initiated" WS event
  │     → Response includes call_session.id
  │
  ▼
[WAITING FOR ASTROLOGER ACCEPT]
  │
  ├─ Astrologer: POST /call/{call_session_id}/accept   ← EXISTING ENDPOINT, NO CHANGE
  │     → Agora token issued, CallSession = ongoing
  │     → No billing tick (package active — bypassed)
  │
  ▼
[CALL ONGOING] ──── WS: PackageSubSessionStarted (countdown resumes)
  │
  ├─ Timer job fires (remaining_duration hits 0)
  │     → Auto-calls CallService::endCall() internally
  │
  ▼
[PACKAGE EXHAUSTED] ──── WS: PackageSessionTerminated
```

---

## 9. Security Rules

| Rule | Enforcement Layer |
|---|---|
| Cannot start sub-session without active package | `SessionTimerService` throws 422 |
| Cannot have two concurrent sub-sessions | `SessionTimerService` checks `whereNull('ended_at')` |
| Cannot switch astrologer mid-package | Astrologer ID bound to `PackagePurchase` |
| Balance check skipped for package users | `ChatService`/`CallService` checks `PackagePurchase` before wallet validation |
| Timer auto-terminates on disconnect | Queue-dispatched `TerminatePackageSessionJob` (delayed by remaining seconds) |
| Linked chat/call ends with sub-session | `endSubSession()` calls `endChat()`/`endCall()` internally |
| No SQL errors leak to client | All controllers wrapped in try-catch, return UUID for support |

---

*Updated: 2026-07-16 v2.1 · astology_backend — reflects Chat/Call integration and currency/unit corrections*
