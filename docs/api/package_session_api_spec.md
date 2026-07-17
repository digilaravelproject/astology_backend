# Prepaid Package Session API — Flutter Integration Specification

**Version**: 2.2 *(Updated: July 2026)*  
**Base URL**: `/api/v1` (e.g., `https://your-domain.com/api/v1`)  
**Headers**:
*   `Authorization: Bearer <auth_token>`
*   `Accept: application/json`
*   `Content-Type: application/json`

---

## 1. Overview & System Flow

The **Prepaid Package Session** system allows a customer (Consumer) to purchase a dedicated time block (prepaid package) for a specific Astrologer (Provider). The consumer can then consume this purchased duration across multiple sequential sub-sessions using either **Chat** or **Call** mode.

No per-minute wallet billing tick occurs during an active package session because the user has already paid for the entire duration upfront.

### 1.1 Complete Lifecycle Overview

```
User purchases package
  → POST /packages/purchase
      → Wallet balance deducted upfront
      → Active PackagePurchase record created (e.g., total_duration = 3600s / 60 mins)
  
  → POST /packages/session/start (mode=chat)
      → Creates real ChatSession internally (status = initiated)
      → Astrologer gets existing "chat_initiated" WebSocket notification
      → Astrologer accepts via existing POST /chat/{id}/accept
      → WebSocket event "PackageSubSessionStarted" broadcasts (countdown begins)
      → Chat messages work via existing endpoints (no wallet billing ticks)
  
  → POST /packages/session/end
      → Deducts elapsed duration from remaining_duration on the PackagePurchase
      → Ends the linked ChatSession automatically
      → WebSocket event "PackageSubSessionEnded" broadcasts
  
  → POST /packages/session/start (mode=call)
      → Creates real CallSession internally (status = initiated)
      → Astrologer accepts via existing POST /call/{id}/accept (Agora token issued)
      → WebSocket event "PackageSubSessionStarted" broadcasts (countdown begins)
      → Call runs (no wallet billing ticks)
  
  → (repeat until remaining_duration reaches 0)
      → Background timer job or last sub-session end exhausts the package
      → WebSocket event "PackageSessionTerminated" broadcasts (forced exit)
```

> **Critical for Flutter**: After calling `POST /packages/session/start`, the response payload contains **both** the `sub_session` record AND the actual initialized `chat_session` or `call_session`. You **MUST** extract the `chat_session.id` or `call_session.id` and use it for all subsequent messaging or Agora calling signaling using existing endpoints.

### 1.2 Key Concepts

| Concept | Description |
|---|---|
| **`PackagePurchase`** | The upfront purchase binding a specific user (consumer) to a specific astrologer. It contains the total duration purchased and the remaining time. |
| **`PackageSubSession`** | The active tracking timer record for a single chat or call session under a package. |
| **`ChatSession` / `CallSession`** | The underlying actual session models used by standard chat/call modules. Created automatically when a sub-session starts. |
| **`remaining_duration`** | The total remaining time left in the package (in seconds). Decreases on every successful sub-session end. |
| **`status: exhausted`** | Marked on the package purchase once `remaining_duration` hits `0`. No further sub-sessions can be initiated. |

---

## 2. API Endpoints Reference

### 2.1 Purchase a Package
*   **Method**: `POST`
*   **Path**: `/packages/purchase`
*   **Role**: Consumer

Deducts the package cost from the consumer's wallet balance and creates an active package purchase associated with the astrologer.

#### Request Body
```json
{
  "astrologer_id": 42
}
```

#### Success Response (`201 Created`)
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

#### Error Response (`422 Unprocessable Content` — e.g., Insufficient Wallet Balance)
```json
{
  "success": false,
  "error_code": "INSUFFICIENT_BALANCE",
  "message": "Insufficient balance. Please recharge your wallet.",
  "tracking_uuid": "c4f712b2-88a1-4bce-9e77-123456789abc"
}
```

---

### 2.2 Check Active Package Status
*   **Method**: `GET`
*   **Path**: `/packages/active-status`
*   **Query Parameters**:
    *   `astrologer_id` (integer, required): The ID of the astrologer.
*   **Role**: Consumer / Astrologer

Call this endpoint on screen/view load to fetch the active package status and recover state if a sub-session was already ongoing (e.g. after an app crash, disconnect, or user lock).

#### Success Response (`200 OK` — When an active package or running sub-session exists)
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

#### Success Response (`200 OK` — When no package or running sub-session exists)
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

### 2.3 Start a Sub-Session
*   **Method**: `POST`
*   **Path**: `/packages/session/start`
*   **Role**: Consumer

Initiates a chat or call session utilizing the active package duration. This endpoint automatically creates the sub-session tracker and a standard `ChatSession` or `CallSession` record under the hood.

#### Request Body
```json
{
  "astrologer_id": 42,
  "mode": "chat",
  "question": "Will I get my dream job in 2026?"
}
```
*   `mode`: Must be either `"chat"` or `"call"`.
*   `question`: Optional parameter. Only applicable for `chat` mode.

#### Success Response (`200 OK` — Chat Mode)
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
      "question": "Will I get my dream job in 2026?",
      "created_at": "2026-07-16T11:25:00.000000Z"
    }
  }
}
```

#### Success Response (`200 OK` — Call Mode)
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

#### Error Response (`422 Unprocessable Content` — e.g., Already has running session, or package expired)
```json
{
  "success": false,
  "error_code": "PACKAGE_SESSION_START_FAILED",
  "message": "You already have an active package sub-session.",
  "tracking_uuid": "c4f712b2-88a1-4bce-9e77-123456789abc"
}
```

---

### 2.4 End a Sub-Session
*   **Method**: `POST`
*   **Path**: `/packages/session/end`
*   **Role**: Consumer / Astrologer

Terminates the active sub-session. The backend automatically calculates the elapsed time, updates `remaining_duration`, and marks the underlying chat or call session as completed.

#### Request Body
```json
{
  "sub_session_id": 4
}
```

#### Success Response (`200 OK`)
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

---

## 3. Real-Time WebSocket Events

WebSocket events are dispatched through private user channels via Pusher/Laravel Echo.  
Channels to subscribe to:
*   `private-user.{userId}`

> **Note**: Standard chat and call event notifications (e.g. message reception, session acceptance) will continue to fire as normal. These package-specific events run in parallel to help manage countdown state and force-terminations.

### 3.1 `PackageSubSessionStarted`
Dispatched to both user and astrologer when the astrologer accepts the sub-session request and the timer actively begins.
*   **Action**: Start/resume the countdown timer in the app.

```json
{
  "event": "App\\Events\\PackageSubSessionStarted",
  "channel": "private-user.5",
  "data": {
    "sub_session_id": 4,
    "package_purchase_id": 17,
    "mode": "chat",
    "remaining_duration": 2100,
    "started_at": "2026-07-16T11:25:00.000000Z"
  }
}
```

### 3.2 `PackageSubSessionEnded`
Dispatched to both user and astrologer when a sub-session is ended normally (either user clicks "End" or normal closure).
*   **Action**: Pause the local timer, calculate remaining time, and transition back to the astrologer's profile/landing page.

```json
{
  "event": "App\\Events\\PackageSubSessionEnded",
  "channel": "private-user.5",
  "data": {
    "sub_session_id": 4,
    "package_purchase_id": 17,
    "mode": "chat",
    "duration_used": 1200,
    "remaining_duration": 900
  }
}
```

### 3.3 `PackageSessionTerminated` ⚠️ Critical
Dispatched by the backend server to both user and astrologer when the total package duration is fully depleted (`remaining_duration` hits `0`), or if the session is forcefully terminated by a system job.
*   **Action**: Immediately close any active chat feed/Agora call screen, dismiss active overlays, show a "Time expired" dialog, and redirect the user back to the main app dashboard.

```json
{
  "event": "App\\Events\\PackageSessionTerminated",
  "channel": "private-user.5",
  "data": {
    "package_purchase_id": 17,
    "mode": "chat",
    "message": "Your package session was forcefully terminated due to time expiration.",
    "remaining_duration": 0,
    "package_status": "exhausted"
  }
}
```

---

## 4. Flutter Integration Guide

Below are the strongly-typed Dart data models and service structures needed to implement this flow.

### 4.1 Data Models

```dart
class PackagePurchase {
  final int id;
  final int userId;
  final int astrologerId;
  final int totalDuration; // in seconds
  final int remainingDuration; // in seconds
  final double purchasePrice;
  final String status; // 'active' | 'exhausted'
  final DateTime createdAt;

  PackagePurchase({
    required this.id,
    required this.userId,
    required this.astrologerId,
    required this.totalDuration,
    required this.remainingDuration,
    required this.purchasePrice,
    required this.status,
    required this.createdAt,
  });

  factory PackagePurchase.fromJson(Map<String, dynamic> json) {
    return PackagePurchase(
      id: json['id'],
      userId: json['user_id'],
      astrologerId: json['astrologer_id'],
      totalDuration: json['total_duration'],
      remainingDuration: json['remaining_duration'],
      purchasePrice: double.parse(json['purchase_price'].toString()),
      status: json['status'],
      createdAt: DateTime.parse(json['created_at']),
    );
  }
}

class PackageSubSession {
  final int id;
  final int packagePurchaseId;
  final String mode; // 'chat' | 'call'
  final int? chatSessionId; // Use this ID for chat API calls
  final int? callSessionId; // Use this ID for call/Agora signaling
  final DateTime startedAt;
  final DateTime? endedAt;
  final int durationUsed;

  PackageSubSession({
    required this.id,
    required this.packagePurchaseId,
    required this.mode,
    this.chatSessionId,
    this.callSessionId,
    required this.startedAt,
    this.endedAt,
    required this.durationUsed,
  });

  factory PackageSubSession.fromJson(Map<String, dynamic> json) {
    return PackageSubSession(
      id: json['id'],
      packagePurchaseId: json['package_purchase_id'],
      mode: json['mode'],
      chatSessionId: json['chat_session_id'],
      callSessionId: json['call_session_id'],
      startedAt: DateTime.parse(json['started_at']),
      endedAt: json['ended_at'] != null ? DateTime.parse(json['ended_at']) : null,
      durationUsed: json['duration_used'] ?? 0,
    );
  }
}

class ChatSession {
  final int id;
  final int consumerId;
  final int providerId;
  final String status;

  ChatSession({
    required this.id,
    required this.consumerId,
    required this.providerId,
    required this.status,
  });

  factory ChatSession.fromJson(Map<String, dynamic> json) {
    return ChatSession(
      id: json['id'],
      consumerId: json['consumer_id'],
      providerId: json['provider_id'],
      status: json['status'],
    );
  }
}

class CallSession {
  final int id;
  final int consumerId;
  final int providerId;
  final String status;
  final String callType;

  CallSession({
    required this.id,
    required this.consumerId,
    required this.providerId,
    required this.status,
    required this.callType,
  });

  factory CallSession.fromJson(Map<String, dynamic> json) {
    return CallSession(
      id: json['id'],
      consumerId: json['consumer_id'],
      providerId: json['provider_id'],
      status: json['status'],
      callType: json['call_type'] ?? 'audio',
    );
  }
}
```

### 4.2 API Services Integration

```dart
class PackageSessionService {
  final Dio apiClient;

  PackageSessionService(this.apiClient);

  /// 1. Purchase a prepaid package
  Future<PackagePurchase> purchasePackage(int astrologerId) async {
    try {
      final response = await apiClient.post(
        '/packages/purchase',
        data: {'astrologer_id': astrologerId},
      );
      return PackagePurchase.fromJson(response.data['data']['purchase']);
    } on DioException catch (e) {
      throw _handleDioError(e);
    }
  }

  /// 2. Check current status & recover session state
  Future<ActiveStatusResponse> getActiveStatus(int astrologerId) async {
    try {
      final response = await apiClient.get(
        '/packages/active-status',
        queryParameters: {'astrologer_id': astrologerId},
      );
      final data = response.data['data'];
      return ActiveStatusResponse(
        hasActivePackage: data['has_active_package'] ?? false,
        purchase: data['package_purchase'] != null 
            ? PackagePurchase.fromJson(data['package_purchase']) 
            : null,
        activeSubSession: data['active_sub_session'] != null 
            ? PackageSubSession.fromJson(data['active_sub_session']) 
            : null,
      );
    } on DioException catch (e) {
      throw _handleDioError(e);
    }
  }

  /// 3. Start a sub-session (Chat or Call)
  Future<StartSubSessionResult> startSubSession({
    required int astrologerId,
    required String mode,
    String? question,
  }) async {
    try {
      final response = await apiClient.post(
        '/packages/session/start',
        data: {
          'astrologer_id': astrologerId,
          'mode': mode,
          if (question != null) 'question': question,
        },
      );
      final data = response.data['data'];
      return StartSubSessionResult(
        subSession: PackageSubSession.fromJson(data['sub_session']),
        remainingDuration: data['remaining_duration'] ?? 0,
        linkedChatSession: data['chat_session'] != null
            ? ChatSession.fromJson(data['chat_session'])
            : null,
        linkedCallSession: data['call_session'] != null
            ? CallSession.fromJson(data['call_session'])
            : null,
      );
    } on DioException catch (e) {
      throw _handleDioError(e);
    }
  }

  /// 4. End a sub-session
  Future<EndSubSessionResult> endSubSession(int subSessionId) async {
    try {
      final response = await apiClient.post(
        '/packages/session/end',
        data: {'sub_session_id': subSessionId},
      );
      final data = response.data['data'];
      return EndSubSessionResult(
        subSession: PackageSubSession.fromJson(data['sub_session']),
        remainingDuration: data['remaining_duration'] ?? 0,
      );
    } on DioException catch (e) {
      throw _handleDioError(e);
    }
  }

  Exception _handleDioError(DioException e) {
    final backendData = e.response?.data;
    if (backendData != null && backendData['error_code'] != null) {
      return Exception("${backendData['error_code']}: ${backendData['message']}");
    }
    return Exception("Network failure: ${e.message}");
  }
}

// Support wrappers for start/end/status API responses
class ActiveStatusResponse {
  final bool hasActivePackage;
  final PackagePurchase? purchase;
  final PackageSubSession? activeSubSession;
  ActiveStatusResponse({required this.hasActivePackage, this.purchase, this.activeSubSession});
}

class StartSubSessionResult {
  final PackageSubSession subSession;
  final int remainingDuration;
  final ChatSession? linkedChatSession;
  final CallSession? linkedCallSession;
  StartSubSessionResult({
    required this.subSession,
    required this.remainingDuration,
    this.linkedChatSession,
    this.linkedCallSession,
  });
}

class EndSubSessionResult {
  final PackageSubSession subSession;
  final int remainingDuration;
  EndSubSessionResult({required this.subSession, required this.remainingDuration});
}
```

---

### 4.3 WebSocket Listeners (Laravel Echo Client)

Setup the WebSocket listener to react instantly to timer starts, pauses, and terminations:

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
        // CRITICAL: immediately close chat/call UI and Agora streams
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

---

## 5. UI Countdown Timer Management

When a package session is active, the app **must** show a countdown timer to the user.

### 5.1 Flutter Timer Widget Implementation

```dart
import 'dart:async';
import 'package:flutter/material.dart';

class PackageTimerWidget extends StatefulWidget {
  final int remainingSeconds;
  final VoidCallback? onTimerExpired;

  const PackageTimerWidget({
    super.key, 
    required this.remainingSeconds,
    this.onTimerExpired,
  });

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
    _startTimer();
  }

  void _startTimer() {
    _timer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (!mounted) return;
      if (_seconds > 0) {
        setState(() {
          _seconds--;
        });
      } else {
        _timer?.cancel();
        if (widget.onTimerExpired != null) {
          widget.onTimerExpired!();
        }
      }
    });
  }

  /// Call this on WebSocket event PackageSessionTerminated or PackageSubSessionEnded
  void forceStop() {
    _timer?.cancel();
    if (mounted) {
      setState(() => _seconds = 0);
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  String get _formatted {
    final minutes = _seconds ~/ 60;
    final seconds = _seconds % 60;
    return '${minutes.toString().padLeft(2, '0')}:${seconds.toString().padLeft(2, '0')}';
  }

  @override
  Widget build(BuildContext context) {
    // Dynamic styling: Turn Red when less than 1 minute remains
    final isUrgent = _seconds < 60;
    
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, py: 6),
      decoration: BoxDecoration(
        color: isUrgent ? Colors.red.withOpacity(0.1) : Colors.green.withOpacity(0.1),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: isUrgent ? Colors.red : Colors.green,
          width: 1.5,
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            Icons.timer,
            size: 16,
            color: isUrgent ? Colors.red : Colors.green,
          ),
          const SizedBox(width: 6),
          Text(
            _formatted,
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: isUrgent ? Colors.red : Colors.green,
            ),
          ),
        ],
      ),
    );
  }
}
```

---

## 6. Security & Business Rules

| Rule | Enforcement Layer | Behavior |
|---|---|---|
| **Auth Requirement** | Middleware (`auth:sanctum`) | Block requests without valid JWT / Session tokens. |
| **No Concurrent Sessions** | `SessionTimerService` | Throws `422` if another sub-session is running for the same user. |
| **Fixed Astrologer Association** | Database & Service Layer | A package purchased for Astrologer A cannot be consumed with Astrologer B. |
| **Balance Check Skip** | `ChatService` / `CallService` | Checks if a package is active. Bypasses normal wallet threshold validation completely. |
| **Background Auto-Termination** | Laravel Queue Jobs (`TerminatePackageSessionJob`) | Server triggers a job delayed by the exact amount of remaining seconds. When the job wakes up, it ends the session if it hasn't already been closed. |
| **Cascade Closures** | `PackageSessionController` | Terminating a `PackageSubSession` automatically calls `endChat()` or `endCall()` internally. |
| **Fail-Safe Logging** | Error Handler | Logs database errors with unique `tracking_uuid` to aid in quick debugging. |

---

## 7. Edge Cases & Reconnection Handling

### 7.1 Page Load / App Restart State Recovery
When the user reopens the app or loads the screen, call `GET /packages/active-status`. 
*   If `active_sub_session` is **not null**:
    *   Find the active sub-session details (e.g. `mode`, `chat_session_id`, `started_at`).
    *   Compute the time difference between `now()` and the `started_at` timestamp.
    *   Deduct this difference from the package's `remaining_duration` to calculate the correct timer display.
    *   Directly route the user to the active Chat/Call screen with this calculated timer.
*   If `active_sub_session` is **null**:
    *   Check if `has_active_package` is true. If so, display the "Start Chat" or "Start Call" button.
    *   Otherwise, display the "Purchase Package" flow.

### 7.2 Handling Astrologer Response (Accept/Reject)
*   The timer **does not** start ticking down when the Consumer calls `POST /packages/session/start`.
*   The timer **only** starts when the Astrologer accepts the call or chat session (causing the backend to fire `PackageSubSessionStarted` event).
*   If the Astrologer rejects or doesn't answer, the session is cancelled without deducting any seconds from the package balance.
