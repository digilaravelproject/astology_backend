# Postman Live Server Chat Testing Guide

This guide explains how to perform end-to-end real-time chat testing using Postman for the Astrology system on the live server (`https://suryapathkundli.com`).

---

## 👥 Test Personas & Database Setup

Before beginning the test, make sure the database is prepared.

### 1. Astrologer (Provider)
- **User ID**: `1`
- **Setup Query**: Run this on the server database to mark the astrologer as online, free, and chat-enabled:
  ```sql
  UPDATE users SET is_online = 1, is_busy = 0 WHERE id = 1;
  UPDATE astrologers SET is_online = 1, is_chat_enabled = 1 WHERE user_id = 1;
  ```

### 2. User (Consumer)
- **User ID**: `20`
- **Setup Query**: Run this to ensure the user has sufficient wallet balance (minimum 5 minutes rate required, e.g., 75 Rs):
  ```sql
  UPDATE wallets SET balance = 500.00 WHERE user_id = 20;
  ```

---

## 📡 Phase 1: WebSocket Connection & Authentication

To simulate both users, you need **2 separate WebSocket Tabs** and **2 HTTP Request Tabs** open side-by-side in Postman.

```
+------------------------------------------+------------------------------------------+
|            Astrologer (ID: 1)            |              User (ID: 20)               |
|  [WebSocket Tab]   [HTTP Request Tab]    |  [WebSocket Tab]   [HTTP Request Tab]    |
+------------------------------------------+------------------------------------------+
```

### 🧑‍💻 PART A: Astrologer (ID: 1) Setup

#### Step 1: Connect to WebSocket
1. Open a new **WebSocket Request** tab in Postman.
2. Enter the secure WebSocket URL:
   ```text
   wss://suryapathkundli.com/app/astrology-key?protocol=7&client=js&version=8.4.0-rc2&flash=false
   ```
3. Go to the **Headers** sub-tab and add:
   - **Key**: `Origin` | **Value**: `https://suryapathkundli.com`
4. Click **Connect**.
5. Look at the WebSocket console logs. Copy the generated `socket_id` from the green connection log (e.g., `11111.22222`).

#### Step 2: Get Auth Signature
1. Open a normal **HTTP Request** tab in Postman.
2. Configure a **POST** request:
   - **URL**: `https://suryapathkundli.com/api/v1/broadcasting/auth`
   - **Headers**:
     - `Authorization`: `Bearer {{ASTROLOGER_1_TOKEN}}`
     - `Accept`: `application/json`
   - **Body** (Select `x-www-form-urlencoded` or `form-data`):
     - `channel_name`: `private-user.1`
     - `socket_id`: `11111.22222` *(Use the socket_id copied in Step 1)*
3. Send the request and copy the `"auth"` value from the JSON response (e.g., `"astrology-key:98765abc..."`).

#### Step 3: Subscribe to Astrologer's Channel
1. Go back to the **Astrologer WebSocket tab**.
2. Send the subscription JSON payload in the message box:
   ```json
   {
       "event": "pusher:subscribe",
       "data": {
           "channel": "private-user.1",
           "auth": "astrology-key:98765abc..."
       }
   }
   ```
3. Verify you receive: `"event": "pusher_internal:subscription_succeeded"`.

---

### 👤 PART B: User (ID: 20) Setup

#### Step 1: Connect to WebSocket
1. Open a second **WebSocket Request** tab in Postman.
2. Enter the same secure WebSocket URL:
   ```text
   wss://suryapathkundli.com/app/astrology-key?protocol=7&client=js&version=8.4.0-rc2&flash=false
   ```
3. Go to the **Headers** sub-tab and add:
   - **Key**: `Origin` | **Value**: `https://suryapathkundli.com`
4. Click **Connect**.
5. Copy the generated `socket_id` from the green connection log (e.g., `33333.44444`).

#### Step 2: Get Auth Signature
1. Open a second **HTTP Request** tab in Postman.
2. Configure a **POST** request:
   - **URL**: `https://suryapathkundli.com/api/v1/broadcasting/auth`
   - **Headers**:
     - `Authorization`: `Bearer {{USER_20_TOKEN}}`
     - `Accept`: `application/json`
   - **Body** (Select `x-www-form-urlencoded` or `form-data`):
     - `channel_name`: `private-user.20`
     - `socket_id`: `33333.44444` *(Use the socket_id copied in Step 1)*
3. Send the request and copy the `"auth"` value from the JSON response (e.g., `"astrology-key:12345xyz..."`).

#### Step 3: Subscribe to User's Channel
1. Go back to the **User WebSocket tab**.
2. Send the subscription JSON payload in the message box:
   ```json
   {
       "event": "pusher:subscribe",
       "data": {
           "channel": "private-user.20",
           "auth": "astrology-key:12345xyz..."
       }
   }
   ```
3. Verify you receive: `"event": "pusher_internal:subscription_succeeded"`.

---

## ⚡ Phase 2: Live Chat Action Flow

Both accounts are now actively listening to their private real-time channels. Follow these steps sequentially to trigger the chat flow.

### Step 1: User (ID: 20) Initiates Chat
- **HTTP Method**: `POST`
- **URL**: `https://suryapathkundli.com/api/v1/chat/initiate`
- **Headers**:
  - `Authorization`: `Bearer {{USER_20_TOKEN}}`
  - `Accept`: `application/json`
- **Body (JSON)**:
  ```json
  {
      "provider_id": 1
  }
  ```
- **Response**: Copy the session **`id`** from the response (e.g., `50`).
- **📡 WebSocket Event**: Go to the **Astrologer WebSocket tab**. You should instantly see the `ChatInitiated` live event containing details about User `20`:
  ```json
  {
      "event": "ChatInitiated",
      "channel": "private-user.1",
      "data": {
          "session": { "id": 50, "status": "initiated", ... },
          "senderData": { "id": 20, "name": "..." }
      }
  }
  ```

---

### Step 2: Astrologer (ID: 1) Accepts Chat
- **HTTP Method**: `POST`
- **URL**: `https://suryapathkundli.com/api/v1/chat/50/accept` *(Replace 50 with the dynamic Session ID)*
- **Headers**:
  - `Authorization`: `Bearer {{ASTROLOGER_1_TOKEN}}`
  - `Accept`: `application/json`
- **📡 WebSocket Event**: Go to the **User WebSocket tab**. You should instantly see the `ChatAccepted` live event containing details about Astrologer `1`:
  ```json
  {
      "event": "ChatAccepted",
      "channel": "private-user.20",
      "data": {
          "session": { "id": 50, "status": "ongoing", ... }
      }
  }
  ```

---

## 💬 Phase 3: Live Message Exchange

### Step 1: User sends a Message
- **HTTP Method**: `POST`
- **URL**: `https://suryapathkundli.com/api/v1/chat/50/message`
- **Headers**:
  - `Authorization`: `Bearer {{USER_20_TOKEN}}`
  - `Accept`: `application/json`
- **Body (JSON)**:
  ```json
  {
      "message": "Hello Guruji! Pranam.",
      "type": "text"
  }
  ```
- **📡 WebSocket Event**: Go to the **Astrologer WebSocket tab**. You will instantly receive the `MessageSent` payload:
  ```json
  {
      "event": "MessageSent",
      "channel": "private-user.1",
      "data": {
          "messageData": {
              "id": 255,
              "chat_session_id": 50,
              "sender_id": 20,
              "receiver_id": 1,
              "message": "Hello Guruji! Pranam."
          }
      }
  }
  ```

---

### Step 2: Astrologer replies to the Message
- **HTTP Method**: `POST`
- **URL**: `https://suryapathkundli.com/api/v1/chat/50/message`
- **Headers**:
  - `Authorization`: `Bearer {{ASTROLOGER_1_TOKEN}}`
  - `Accept`: `application/json`
- **Body (JSON)**:
  ```json
  {
      "message": "Kalyan ho vats! Kaise hain aap?",
      "type": "text"
  }
  ```
- **📡 WebSocket Event**: Go to the **User WebSocket tab**. You will instantly receive the `MessageSent` payload reply:
  ```json
  {
      "event": "MessageSent",
      "channel": "private-user.20",
      "data": {
          "messageData": {
              "id": 256,
              "chat_session_id": 50,
              "sender_id": 1,
              "receiver_id": 20,
              "message": "Kalyan ho vats! Kaise hain aap?"
          }
      }
  }
  ```

---

## 💓 Keeping the connection alive (Testing Tip)
WebSockets naturally close after 30 seconds of inactivity due to the Pusher heartbeat protocol. 
To prevent automatic disconnection, manually send this message from **both** of your Postman WebSocket tabs every 20 seconds:
```json
{
    "event": "pusher:ping"
}
```
You will receive `{"event":"pusher:pong"}` from the server, and your testing connection will remain active indefinitely!
