# 📘 Chat Assistance API & WebSocket Integration Specification

> **Target Audience:** Frontend Developers (Flutter / Mobile / Web)  
> **Backend Protocol:** HTTP REST APIs + Laravel Echo (WebSocket)  
> **Auth Scheme:** Bearer Token (`Authorization: Bearer <sanctum_token>`)  
> **Base URL Path:** `/api/v1/chat-assistance`

---

## 1. System Overview & Business Rules

1. **Free / Assistant Chat Consultation:** Allows consumers and astrologers to chat directly outside or during a call session.
2. **Session Persistence:** A single unique chat assistance session exists between a specific user and astrologer. Calling `initiate` reuses the existing active session ID.
3. **History Window:** Only messages from the last **3 days** are retrieved in message history.
4. **Daily Astrologer Reply Limit:** Astrologers have an admin-configured daily reply limit (e.g., maximum 5 free replies per day). If the limit is exhausted, outgoing messages from the astrologer are blocked and a limit alert is broadcasted.
5. **Real-time Status Tracking:** Supports full status transition:
   - `Sent` (Message created on server)
   - `Delivered` (`is_delivered: true` - received on recipient device)
   - `Seen` / `Read` (`is_read: true` - opened/read in chat screen)

---

## 2. HTTP REST API Endpoints

### 2.1 Initiate / Get Session
Establish or retrieve the persistent chat assistance session between user and astrologer.

* **Endpoint:** `POST /api/v1/chat-assistance/initiate`
* **Headers:**
  ```http
  Authorization: Bearer <token>
  Accept: application/json
  Content-Type: application/json
  ```
* **Request Body:**
  ```json
  {
    "provider_id": 42,
    "call_session_id": 105
  }
  ```
  *(Note: `call_session_id` is optional, used when initiating during a live call).*

* **Success Response (`200 OK`):**
  ```json
  {
    "success": true,
    "status": "success",
    "message": "Chat assistance initiated successfully",
    "data": {
      "session": {
        "id": 15,
        "consumer_id": 12,
        "provider_id": 42,
        "created_at": "2026-08-22T10:00:00.000000Z",
        "updated_at": "2026-08-22T10:00:00.000000Z"
      }
    }
  }
  ```

---

### 2.2 Send Message
Send a text message, attachment file, image, or media. Supports JSON or `multipart/form-data`.

* **Endpoint:** `POST /api/v1/chat-assistance/{sessionId}/message`
* **Headers:**
  ```http
  Authorization: Bearer <token>
  Accept: application/json
  ```
* **Request Body (JSON or Form-Data):**
  | Parameter | Type | Required? | Description |
  | :--- | :--- | :--- | :--- |
  | `message` | `string` | Optional* | Text message content (Required if no attachment) |
  | `file` / `attachment` | `File` (Binary) | Optional* | Attachment file (Max 10MB) |
  | `attachment_url` | `string` | Optional | Direct URL to uploaded asset |
  | `type` | `string` | Optional | `text`, `image`, `document`, `file`, `audio`, `video` (Defaults to `text` or `image`) |
  | `call_session_id` | `integer` | Optional | Link message with active call |

* **Sample Request (JSON):**
  ```json
  {
    "message": "Pranam Guruji, please review my birth chart details.",
    "type": "text",
    "call_session_id": 105
  }
  ```

* **Success Response (`200 OK`):**
  ```json
  {
    "success": true,
    "status": "success",
    "message": "Message sent successfully",
    "data": {
      "message": {
        "id": 204,
        "chat_assistance_session_id": 15,
        "sender_id": 12,
        "receiver_id": 42,
        "message": "Pranam Guruji, please review my birth chart details.",
        "attachment_url": null,
        "type": "text",
        "is_read": false,
        "is_delivered": false,
        "call_session_id": 105,
        "created_at": "2026-08-22T10:05:00.000000Z",
        "updated_at": "2026-08-22T10:05:00.000000Z"
      }
    }
  }
  ```

* **Error Response (`400 Bad Request` - Limit Reached):**
  ```json
  {
    "success": false,
    "status": "error",
    "message": "Daily message reply limit reached. You cannot send more replies today."
  }
  ```

---

### 2.3 Retrieve Messages (History)
Fetch chronological paginated message history for the active session (filtered to the last 3 days).

* **Endpoint:** `GET /api/v1/chat-assistance/{sessionId}/messages?page=1`
* **Headers:**
  ```http
  Authorization: Bearer <token>
  Accept: application/json
  ```

* **Success Response (`200 OK`):**
  ```json
  {
    "success": true,
    "status": "success",
    "message": "Messages retrieved successfully",
    "data": {
      "current_page": 1,
      "data": [
        {
          "id": 201,
          "chat_assistance_session_id": 15,
          "sender_id": 12,
          "receiver_id": 42,
          "message": "Hello Guruji",
          "attachment_url": null,
          "type": "text",
          "is_read": true,
          "is_delivered": true,
          "call_session_id": null,
          "created_at": "2026-08-22T09:30:00.000000Z",
          "updated_at": "2026-08-22T09:30:05.000000Z"
        },
        {
          "id": 204,
          "chat_assistance_session_id": 15,
          "sender_id": 12,
          "receiver_id": 42,
          "message": "Pranam Guruji, please review my birth chart details.",
          "attachment_url": null,
          "type": "text",
          "is_read": false,
          "is_delivered": false,
          "call_session_id": 105,
          "created_at": "2026-08-22T10:05:00.000000Z",
          "updated_at": "2026-08-22T10:05:00.000000Z"
        }
      ],
      "first_page_url": "http://api.domain.com/api/v1/chat-assistance/15/messages?page=1",
      "from": 1,
      "last_page": 1,
      "last_page_url": "http://api.domain.com/api/v1/chat-assistance/15/messages?page=1",
      "next_page_url": null,
      "path": "http://api.domain.com/api/v1/chat-assistance/15/messages",
      "per_page": 30,
      "prev_page_url": null,
      "to": 2,
      "total": 2,
      "chat_assistance_session_id": 15,
      "total_orders": 145,
      "orders_count": 145,
      "orders_formatted": "145+ orders"
    }
  }
  ```

---

### 2.4 Sync Message Status (Delivered & Seen Receipts)
Mark batch of messages as `delivered` or `seen`.

* **Endpoint:** `POST /api/v1/chat-assistance/{sessionId}/sync-status`
* **Headers:**
  ```http
  Authorization: Bearer <token>
  Accept: application/json
  Content-Type: application/json
  ```
* **Request Body:**
  ```json
  {
    "status": "seen",
    "message_ids": [201, 204]
  }
  ```
  *(Status options: `"delivered"` or `"seen"`)*

* **Success Response (`200 OK`):**
  ```json
  {
    "success": true,
    "status": "success",
    "message": "Status synced successfully",
    "data": null
  }
  ```

---

### 2.5 Get Astrologer Daily Limit Status
Check remaining quota for the logged-in astrologer for the current date.

* **Endpoint:** `GET /api/v1/chat-assistance/astrologer/status`
* **Headers:**
  ```http
  Authorization: Bearer <token>
  Accept: application/json
  ```

* **Success Response (`200 OK`):**
  ```json
  {
    "success": true,
    "status": "success",
    "message": "Astrologer limits status retrieved successfully",
    "data": {
      "limit": 5,
      "used": 2,
      "remaining": 3
    }
  }
  ```

---

### 2.6 Get All Chat Assistance Sessions (Chat List Screen)
Fetch list of all assistance chat threads for the logged-in user with latest message previews.

* **Endpoint:** `GET /api/v1/chat-assistance/sessions?per_page=15&page=1`
* **Headers:**
  ```http
  Authorization: Bearer <token>
  Accept: application/json
  ```

* **Success Response (`200 OK`):**
  ```json
  {
    "success": true,
    "status": "success",
    "message": "Chat assistance sessions retrieved successfully",
    "data": {
      "current_page": 1,
      "data": [
        {
          "id": 15,
          "chat_assistance_session_id": 15,
          "consumer_id": 12,
          "provider_id": 42,
          "created_at": "2026-08-22T09:00:00.000000Z",
          "updated_at": "2026-08-22T10:05:00.000000Z",
          "consumer": {
            "id": 12,
            "name": "Rahul Verma",
            "profile_photo": "https://api.domain.com/storage/profiles/user_12.jpg"
          },
          "provider": {
            "id": 42,
            "name": "Acharya Sharma",
            "profile_photo": "https://api.domain.com/storage/profiles/astro_42.jpg",
            "total_orders": 240,
            "orders_count": 240,
            "orders_formatted": "240+ orders"
          },
          "latest_message": {
            "id": 204,
            "chat_assistance_session_id": 15,
            "sender_id": 12,
            "receiver_id": 42,
            "message": "Pranam Guruji, please review my birth chart details.",
            "attachment_url": null,
            "type": "text",
            "is_read": false,
            "is_delivered": false,
            "call_session_id": 105,
            "created_at": "2026-08-22T10:05:00.000000Z"
          }
        }
      ],
      "per_page": 15,
      "total": 1
    }
  }
  ```

---

## 3. WebSocket Real-Time Events (Laravel Echo)

### 3.1 Authorized Broadcast Channels
Clients must subscribe with `Authorization: Bearer <token>` to private channels:

1. **User Channel (Direct user notifications):**
   - Channel Name: `private-user.{userId}`
2. **Chat Room Channel (Dedicated room events):**
   - Channel Name: `private-chat-assistance.{sessionId}`

---

### 3.2 Event: `ChatAssistanceMessageSent`
Triggered whenever a message is sent in the session. Broadcasted on **both** `private-user.{receiverId}` and `private-chat-assistance.{sessionId}`.

* **Event Name:** `ChatAssistanceMessageSent`
* **Broadcast Payload:**
  ```json
  {
    "messageData": {
      "id": 204,
      "chat_assistance_session_id": 15,
      "sender_id": 12,
      "receiver_id": 42,
      "message": "Pranam Guruji, please review my birth chart details.",
      "attachment_url": null,
      "type": "text",
      "is_read": false,
      "is_delivered": false,
      "call_session_id": 105,
      "created_at": "2026-08-22T10:05:00.000000Z",
      "sender": {
        "id": 12,
        "name": "Rahul Verma",
        "avatar": "https://api.domain.com/storage/profiles/user_12.jpg",
        "role": "user"
      }
    },
    "receiverId": 42
  }
  ```

---

### 3.3 Event: `ChatAssistanceMessageStatusUpdated`
Triggered when the recipient marks messages as `delivered` or `seen`. Broadcasted to the sender on `private-user.{senderId}`.

* **Event Name:** `ChatAssistanceMessageStatusUpdated`
* **Broadcast Payload:**
  ```json
  {
    "messageIds": [201, 204],
    "status": "seen",
    "receiverId": 12,
    "sessionId": 15,
    "updatedBy": 42,
    "timestamp": "2026-08-22T10:06:00.000000Z"
  }
  ```

---

### 3.4 Event: `ChatAssistanceInitiated`
Triggered when a consumer initiates a new session with an astrologer. Broadcasted to the astrologer on `private-user.{astrologerUserId}`.

* **Event Name:** `ChatAssistanceInitiated`
* **Broadcast Payload:**
  ```json
  {
    "session": {
      "id": 15,
      "consumer_id": 12,
      "provider_id": 42,
      "created_at": "2026-08-22T10:00:00.000000Z",
      "updated_at": "2026-08-22T10:00:00.000000Z"
    },
    "senderData": {
      "id": 12,
      "name": "Rahul Verma",
      "profile_photo": "https://api.domain.com/storage/profiles/user_12.jpg"
    }
  }
  ```

---

### 3.5 Event: `ChatAssistanceLimitReached`
Triggered when the astrologer attempts to send or consumes their last free message for the day. Broadcasted on `private-user.{astrologerUserId}`.

* **Event Name:** `ChatAssistanceLimitReached`
* **Broadcast Payload:**
  ```json
  {
    "astrologerId": 42,
    "message": "Daily reply limit reached."
  }
  ```

---

## 4. Message Bubble Status Indicators

| Indicator | Status Flags | Meaning |
| :--- | :--- | :--- |
| **Single Gray Checkmark** (`✓`) | `is_delivered: false`, `is_read: false` | Message sent to server |
| **Double Gray Checkmark** (`✓✓`) | `is_delivered: true`, `is_read: false` | Message delivered to recipient device |
| **Double Blue Checkmark** (`✓✓`) | `is_delivered: true`, `is_read: true` | Message opened & read in chat view |

---

## 5. Summary Flow for Frontend Developer

1. **Open / Start Chat:** Call `POST /api/v1/chat-assistance/initiate` ➡️ Subscribe to `private-chat-assistance.{sessionId}` and `private-user.{myUserId}`.
2. **Load Initial Messages:** Call `GET /api/v1/chat-assistance/{sessionId}/messages`.
3. **Mark Messages Read:** Identify unread messages from opposite party (`sender_id != myId` and `is_read == false`) ➡️ Call `POST /api/v1/chat-assistance/{sessionId}/sync-status` with `status: "seen"`.
4. **Send Message:** Call `POST /api/v1/chat-assistance/{sessionId}/message`.
5. **Listen to Incoming Messages:** Handle `ChatAssistanceMessageSent` event ➡️ Append to message list ➡️ Immediately call sync-status (`delivered` or `seen`).
6. **Listen to Status Updates:** Handle `ChatAssistanceMessageStatusUpdated` ➡️ Update tick marks of matching `messageIds` to `delivered` or `seen`.
7. **Handle Daily Limit:** For astrologer, if `ChatAssistanceLimitReached` received or error 400 with limit message ➡️ Disable input box and show *"Daily reply limit reached"* alert.
