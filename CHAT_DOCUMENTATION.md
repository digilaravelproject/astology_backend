# 🔮 Chat & Presence: The Complete Guide (Beginner to Pro)

This guide covers everything from getting online to handling real-time "Blue Checks" (Seen) and "Typing" indicators. Use this for testing in Postman or building your Flutter/Frontend app.

---

## 🏗️ Phase 1: Preparation (Authentication)

Before doing anything, you need an **Auth Token (Bearer Token)** for both users.

- **User ID 12**: The Consumer (Customer)
- **User ID 13**: The Provider (Astrologer)

---

## 🟢 Phase 2: Getting the Astrologer "Online" (Presence)

The system checks if an Astrologer is available. If they aren't "Online", the chat initiation will fail.

**Action**: The Astrologer (User 13) must call the "Pulse" API.

- **API**: `POST /api/v1/presence/pulse`
- **Header**: `Authorization: Bearer {{ASTRO_TOKEN}}`
- **Result**: User 13 is now `is_online: true` and available for chats.

---

## 🔌 Phase 3: The WebSocket Connection (The Handshake)

Open two separate **WebSocket** tabs in Postman (one for User 12, one for Astro 13).

1.  **WebSocket URL**:
    `ws://127.0.0.1:8080/app/{{REVERB_APP_KEY}}?protocol=7&client=js&version=8.4.0`
2.  **Headers**:
    - `Origin`: `http://localhost`
3.  **Connection Message**: Once connected, you will see a `pusher:connection_established` message.
4.  **Copy your `socket_id`**: Look for the `socket_id` in the message (e.g., `123.456`). **You need this for Phase 4.**

> [!TIP]
> **Timeout Fix**: The connection stays alive for **5 minutes** of inactivity. You don't need to reconnect every 30 seconds.

---

## 🔑 Phase 4: Channel Authorization (Private Rooms)

Laravel protects user data. You must "Authorize" your specific `socket_id` to listen to a private channel.

**Step A: Get the Auth Token**

1.  **API**: `POST /api/v1/broadcasting/auth`
2.  **Body (form-data)**:
    - `channel_name`: `private-user.{{YOUR_ID}}` (e.g., `private-user.12`)
    - `socket_id`: `{{PASTE_SOCKET_ID_HERE}}`
3.  **Response**: `{ "auth": "app_key:signature..." }`

**Step B: Subscribe on WebSocket**
Back in your **WebSocket tab**, send this JSON:

```json
{
    "event": "pusher:subscribe",
    "data": {
        "channel": "private-user.{{YOUR_ID}}",
        "auth": "{{AUTH_STRING_FROM_ABOVE}}"
    }
}
```

**Result**: You should see `pusher_internal:subscription_succeeded`.

---

## 🔄 Phase 5: The Chat Lifecycle (Standard Flow)

### Step 1: User 12 Initiates Chat

- **API**: `POST /api/v1/chat/initiate`
- **Body**: `{"provider_id": 13}`
- **Event (Astro 13 WebSocket)**:
    ```json
    {
        "event": "ChatInitiated",
        "data": {
            "session": { "id": 101, "status": "initiated", ... },
            "senderData": { "id": 12, "name": "Test User", "city": "Mumbai", ... }
        }
    }
    ```

### Step 2: Astro 13 Accepts Chat

- **API**: `POST /api/v1/chat/101/accept`
- **Event (User 12 WebSocket)**:
    ```json
    {
        "event": "ChatAccepted",
        "data": {
            "session": { "id": 101, "status": "ongoing", ... },
            "providerData": { "id": 13, "name": "Astro Guru", "astrologer": { ... } }
        }
    }
    ```

### Step 3: Sending a Message

- **API**: `POST /api/v1/chat/101/message`
- **Body**: `{"message": "Hello Guru!", "type": "text"}`
- **Event (Other Person WebSocket)**:
    ```json
    {
        "event": "MessageSent",
        "data": {
            "message": { "id": 500, "message": "Hello Guru!", "sender_id": 12, ... }
        }
    }
    ```

---

## 🔥 Phase 6: Automated Real-Time Indicators (Pro Features)

These happen **automatically** in your app code via **WebSocket Whispers**. No separate API hit is required for the real-time UI update.

### ⌨️ Typing Indicator

**Trigger**: On every keystroke in the message box.

```json
{
    "event": "client-typing",
    "data": { "session_id": 101, "is_typing": true },
    "channel": "private-user.{{RECEIVER_ID}}"
}
```

### ✅✅ Delivered (Status: 2 Checks)

**Trigger**: Sent by the **Receiver** the moment they receive the `MessageSent` frame.

```json
{
    "event": "client-delivered",
    "data": { "session_id": 101, "message_ids": [500] },
    "channel": "private-user.{{SENDER_ID}}"
}
```

### 🔵🔵 Seen (Status: Blue Checks)

**Trigger**: Sent by the **Receiver** when they have the chat open and the message is visible.

```json
{
    "event": "client-seen",
    "data": { "session_id": 101, "message_ids": [500] },
    "channel": "private-user.{{SENDER_ID}}"
}
```

---

## 📜 Phase 7: History & Synchronization

Standard `GET` APIs for loading old data or syncing database status.

1.  **Get Sessions**: `GET /api/v1/chat/sessions` (List of chats).
2.  **Get History**: `GET /api/v1/chat/{{id}}/messages` (Chat messages).
3.  **Sync Status**: `POST /api/v1/chat/{{id}}/sync-status`
    - **Usage**: Use this in the background to save the "Delivered" or "Seen" status to the database permanently.
    - **Body**: `{"status": "seen", "message_ids": [500, 501]}`

---

## 💡 Summary of Routes Table

| Route                    | Method  | Role           | When to use?                             |
| :----------------------- | :------ | :------------- | :--------------------------------------- |
| `/presence/pulse`        | POST    | **Get Online** | MUST do first!                           |
| `/chat/sessions`         | **GET** | **List**       | To show main chat list.                  |
| `/chat/initiate`         | POST    | **Start**      | Ring the provider.                       |
| `/chat/{id}/accept`      | POST    | **Answer**     | Pick up the chat.                        |
| `/chat/{id}/message`     | POST    | **Send**       | Send text/images.                        |
| `/chat/{id}/sync-status` | POST    | **Save State** | Background DB update for Seen/Delivered. |
| `/chat/{id}/messages`    | **GET** | **History**    | Load old messages.                       |

## 🛑 Troubleshooting for Beginners

1.  **"Astrologer is Offline"**: Make sure you hit `/presence/pulse` with the Astrologer's token.
2.  **Auth String**: Remember that if you "Connect" again, your `socket_id` changes, so you need a NEW auth string.
3.  **Channel Names**: Ensure you are using `private-user.{{id}}` correctly.
