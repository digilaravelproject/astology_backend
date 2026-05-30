# Astology WebSocket Integration Guide for Flutter

## Why Backend Won't Change

The backend `/api/v1/broadcasting/auth` endpoint follows the **Pusher protocol standard**. It is designed to work with Pusher SDKs across all platforms (Web, Flutter, React Native). The endpoint does exactly 3 things correctly:

1. Authenticates the user via Bearer token
2. Checks channel authorization (via `routes/channels.php`)
3. Returns the HMAC signature

This is the **standard, expected format** that every Pusher SDK (including Flutter's `pusher_client`) understands automatically. No backend change is needed.

---

## Current Problem (Manual 3 Steps)

If you are using **raw WebSocket** or making **manual HTTP calls**, you are doing:

```
Step 1: Connect to WebSocket → manually copy socket_id from logs
Step 2: HTTP POST /api/v1/broadcasting/auth → manually copy auth signature
Step 3: Send pusher:subscribe JSON → manually craft and send
```

This is unnecessary. The Pusher SDK handles all 3 steps **automatically**.

---

## Solution: Use `pusher_client` Package (1 Line Only)

### 1. Install Package

```yaml
# pubspec.yaml
dependencies:
  pusher_client: ^2.1.0
```

### 2. Initialize Pusher with Auth Token

```dart
import 'package:pusher_client/pusher_client.dart';

class WebSocketService {
  late PusherClient pusher;
  late Channel channel;

  void connect(String token, int userId) {
    // ---------- ONE TIME SETUP ----------
    pusher = PusherClient('astrology-key', PusherOptions(
      host: 'suryapathkundli.com',
      path: '/app/astrology-key',
      port: 443,
      encrypted: true,
      // This endpoint is called AUTOMATICALLY by the SDK
      authEndpoint: 'https://suryapathkundli.com/api/v1/broadcasting/auth',
      // Your existing Bearer token — configured once, used for all subscriptions
      auth: PusherAuth(headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      }),
    ));

    // Connection state tracking (optional)
    pusher.onConnectionStateChange((state) {
      print('Connection: ${state.currentState}');
    });

    pusher.onConnectionError((error) {
      print('Connection error: $error');
    });
  }

  void subscribeToUserChannel(int userId) {
    // ---------- ONE LINE — EVERYTHING HAPPENS AUTOMATICALLY ----------
    channel = pusher.subscribe('private-user.$userId');

    channel.bind('pusher:subscription_succeeded', () {
      print('✅ Subscribed to private-user.$userId');
    });

    channel.bind('pusher:subscription_error', (error) {
      print('❌ Subscription failed: $error');
    });

    // ---- Listen to events ----
    channel.bind('ChatInitiated', (data) {
      print('New chat request: $data');
    });

    channel.bind('ChatAccepted', (data) {
      print('Chat accepted: $data');
    });

    channel.bind('ChatEnded', (data) {
      print('Chat ended: $data');
    });

    channel.bind('MessageSent', (data) {
      print('New message: $data');
    });

    channel.bind('MessageStatusUpdated', (data) {
      print('Message status updated: $data');
    });

    // Call events
    channel.bind('CallInitiated', (data) {
      print('Incoming call: $data');
    });

    channel.bind('CallAccepted', (data) {
      print('Call accepted: $data');
    });

    channel.bind('CallEnded', (data) {
      print('Call ended: $data');
    });

    channel.bind('IceCandidateSent', (data) {
      print('ICE candidate: $data');
    });
  }
}
```

### 3. Usage — Just 2 Lines!

```dart
final wsService = WebSocketService();

// Line 1: Setup with your Bearer token
wsService.connect('your_bearer_token_here', 1);

// Line 2: Subscribe (SDK handles everything internally)
wsService.subscribeToUserChannel(1);
```

---

## What the SDK Does Automatically

| Your Code | SDK Handles Behind the Scenes |
|---|---|
| `pusher.subscribe('private-user.1')` | ① Connects to WebSocket → receives socket_id<br>② Calls `POST /api/v1/broadcasting/auth` with Bearer token + socket_id + channel → receives auth signature<br>③ Sends `pusher:subscribe` with the auth signature<br>④ Returns the subscribed channel ready for events |

---

## What You No Longer Need to Do

| ❌ Before (Manual) | ✅ After (SDK) |
|---|---|
| Open WebSocket connection manually | SDK connects automatically |
| Copy socket_id from console logs | SDK manages socket_id internally |
| Make HTTP POST to auth endpoint | SDK calls auth endpoint automatically |
| Copy auth signature from response | SDK handles the signature |
| Craft JSON: `{"event": "pusher:subscribe", ...}` | SDK sends the correct protocol message |
| Check subscription succeeded manually | SDK fires `pusher:subscription_succeeded` event |

---

## Connection Handling

```dart
// Reconnect on disconnect (SDK does this automatically)
pusher.onConnectionStateChange((state) {
  switch (state.currentState) {
    case ConnectionState.connected:
      print('Connected to WebSocket');
      break;
    case ConnectionState.disconnected:
      print('Disconnected — SDK will auto-reconnect');
      break;
    case ConnectionState.waitingToReconnect:
      print('Attempting reconnection...');
      break;
  }
});
```

---

## Complete Minimal Example

```dart
import 'package:flutter/material.dart';
import 'package:pusher_client/pusher_client.dart';

void main() => runApp(MyApp());

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(home: WebSocketScreen());
  }
}

class WebSocketScreen extends StatefulWidget {
  @override
  _WebSocketScreenState createState() => _WebSocketScreenState();
}

class _WebSocketScreenState extends State<WebSocketScreen> {
  late PusherClient pusher;
  late Channel channel;
  List<String> logs = [];

  @override
  void initState() {
    super.initState();
    initializeWebSocket();
  }

  void initializeWebSocket() {
    // Replace with your actual token and user ID
    String token = 'YOUR_BEARER_TOKEN';
    int userId = 1;

    pusher = PusherClient('astrology-key', PusherOptions(
      host: 'suryapathkundli.com',
      path: '/app/astrology-key',
      port: 443,
      encrypted: true,
      authEndpoint: 'https://suryapathkundli.com/api/v1/broadcasting/auth',
      auth: PusherAuth(headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      }),
    ));

    channel = pusher.subscribe('private-user.$userId');

    channel.bind('pusher:subscription_succeeded', () {
      setState(() => logs.add('✅ Subscribed successfully'));
    });

    channel.bind('ChatInitiated', (data) {
      setState(() => logs.add('📩 New chat: $data'));
    });

    channel.bind('MessageSent', (data) {
      setState(() => logs.add('💬 New message: $data'));
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('WebSocket Test')),
      body: ListView.builder(
        itemCount: logs.length,
        itemBuilder: (_, i) => ListTile(title: Text(logs[i])),
      ),
    );
  }
}
```

---

## Events Reference

| Event | Channel | Triggered When |
|---|---|---|
| `ChatInitiated` | `private-user.{provider_id}` | New chat request |
| `ChatAccepted` | `private-user.{consumer_id}` | Chat accepted by astrologer |
| `ChatEnded` | `private-user.{receiverId}` | Chat session ended |
| `MessageSent` | `private-user.{receiverId}` | New message in chat |
| `MessageStatusUpdated` | `private-user.{receiverId}` | Message delivery/read status |
| `CallInitiated` | `private-user.{provider_id}` | Incoming call offer |
| `CallAccepted` | `private-user.{consumer_id}` | Call accepted |
| `CallEnded` | `private-user.{receiverId}` | Call ended |
| `IceCandidateSent` | `private-user.{receiverId}` | WebRTC ICE candidate |
| `PresenceUpdated` | `presence-room` | User online/offline status |

---

## Troubleshooting

| Issue | Solution |
|---|---|
| **401 on auth endpoint** | Pass a valid Bearer token obtained from login API |
| **Subscription fails** | The channel `private-user.1` can only be subscribed by user with ID = 1. Check your userId matches. |
| **Connection not establishing** | Verify `host` and `port`. Production: `suryapathkundli.com:443` |
| **Events not firing** | Confirm you are subscribed to the correct channel. Check that the backend is broadcasting events. |
| **Reconnection issues** | The SDK auto-reconnects. Check `onConnectionStateChange` for status. |
| **TLS/SSL errors** | Set `encrypted: true` for production, `encrypted: false` for local dev (127.0.0.1) |

---

## Architecture Overview (How the 3 Steps Become 1)

```
[Flutter App]                    [Laravel Backend]             [Reverb WebSocket]
     |                                  |                            |
     |--- (1) WebSocket Connect ------->|                            |
     |<-- socket_id ---------------------|                            |
     |                                  |                            |
     |--- (2) POST /auth (Bearer) ----->|                            |
     |                                  |--- Authenticate user ----->|
     |                                  |--- Authorize channel ----->|
     |<-- auth signature ----------------|                            |
     |                                  |                            |
     |--- (3) pusher:subscribe (auth) ->|                            |
     |                                  |--- Verify HMAC ----------->|
     |<-- subscription_succeeded --------|                            |
```

**All 3 steps above happen automatically when you call `pusher.subscribe()`.**
The developer writes only **one line** — the SDK handles the rest.

---

## Key Takeaway

> **Do NOT manually connect, manually call auth, and manually subscribe.**
> Use `pusher.subscribe(channelName)` and let the SDK handle everything.
> 
> Before: 3 manual steps (connect → auth API → subscribe)
> After: 1 method call (`pusher.subscribe()`)
