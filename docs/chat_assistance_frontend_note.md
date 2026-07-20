# Chat Assistance System - Frontend (Flutter) Integration Note

This integration document outlines the HTTP APIs and real-time WebSocket events for the Chat Assistance system. It incorporates the idempotent single-session thread model, storage prefix formatting, and event synchronization specifications.

---

## 1. Architectural Design (WhatsApp-Style Threading)
*   **Idempotency:** A single persistent session is shared per consumer-astrologer pair. Calling the initiate endpoint multiple times will **always return the same session ID** rather than generating duplicates.
*   **Media paths:** Profile photos and attachments are returned using the clean relative storage path format (e.g. `users/38/profile_photo/xyz.jpg`) without absolute domain or `storage/` prefixes. Prefix this with your configured base URL + `/storage/` on the client.
*   **Broadcasting Channel:** All events are broadcasted on a secure private user channel: `private-user.<user_id>` (which maps to the authenticated `user.{id}` channel on the backend).

---

## 2. HTTP API Endpoints Reference

### 2.1 Initiate Chat Assistance Session
Retrieves the existing session or creates a new one between the user and the astrologer.

*   **URL:** `/api/v1/chat-assistance/initiate`
*   **Method:** `POST`
*   **Headers:**
    ```http
    Authorization: Bearer <token>
    Accept: application/json
    Content-Type: application/json
    ```
*   **Request Payload:**
    ```json
    {
      "provider_id": 42,
      "call_session_id": 105 // Optional
    }
    ```
*   **Success Response (`200 OK`):**
    ```json
    {
      "status": "success",
      "message": "Chat assistance initiated successfully",
      "data": {
        "session": {
          "id": 15,
          "consumer_id": 12,
          "provider_id": 42,
          "created_at": "2026-07-20T13:45:00.000000Z",
          "updated_at": "2026-07-20T13:45:00.000000Z"
        }
      }
    }
    ```

---

### 2.2 Send Message
*   **URL:** `/api/v1/chat-assistance/{sessionId}/message`
*   **Method:** `POST`
*   **Headers:** Same as above
*   **Request Payload:**
    ```json
    {
      "message": "Hello", // Required if attachment_url is null
      "attachment_url": "users/12/attachments/doc.pdf", // Optional
      "type": "text" // Optional: "text" | "image"
    }
    ```
*   **Success Response (`200 OK`):**
    ```json
    {
      "status": "success",
      "message": "Message sent successfully",
      "data": {
        "message": {
          "id": 182,
          "chat_assistance_session_id": 15,
          "sender_id": 12,
          "receiver_id": 42,
          "message": "Hello",
          "attachment_url": null,
          "type": "text",
          "is_read": false,
          "is_delivered": false,
          "created_at": "2026-07-20T13:45:05.000000Z",
          "updated_at": "2026-07-20T13:45:05.000000Z"
        }
      }
    }
    ```
*   **Error Response (`400 Bad Request` - Limit Reached):**
    ```json
    {
      "status": "error",
      "message": "Daily message reply limit reached. You cannot send more replies today."
    }
    ```

---

### 2.3 Fetch Messages History (Last 3 Days)
*   **URL:** `/api/v1/chat-assistance/{sessionId}/messages?page=1`
*   **Method:** `GET`
*   **Success Response (`200 OK`):** Paginated messages history.

---

### 2.4 Synchronize Message Receipts
*   **URL:** `/api/v1/chat-assistance/{sessionId}/sync-status`
*   **Method:** `POST`
*   **Request Payload:**
    ```json
    {
      "status": "seen", // "delivered" | "seen"
      "message_ids": [182]
    }
    ```
*   **Success Response (`200 OK`):**
    ```json
    {
      "status": "success",
      "message": "Status synced successfully",
      "data": null
    }
    ```

---

### 2.5 Active Session ID in Call & Chat Histories
When fetching astrologer's regular call and chat lists via the following endpoints:
*   `GET /api/v1/call/sessions/astrologer`
*   `GET /api/v1/chat/sessions/astrologer`

The response payload contains `chat_assistance_session_id` on both the root session object and the `consumer` object. You can use this ID to direct the astrologer directly to the corresponding free assistance chat room.

Example snippet from Call History response:
```json
{
  "id": 85,
  "consumer_id": 12,
  "provider_id": 42,
  "status": "completed",
  "chat_assistance_session_id": 15,
  "consumer": {
    "id": 12,
    "name": "Amit Kumar",
    "profile_photo": "users/12/profile_photo/1.jpg",
    "chat_assistance_session_id": 15
  }
}
```

---

## 3. Real-Time WebSockets Events

Subscribe to Laravel Echo private channel: **`private-user.<your_user_id>`**.

### 3.1 `ChatAssistanceInitiated`
Fired to the astrologer when a consumer starts a chat assistance thread.
*   **Event Name:** `ChatAssistanceInitiated`
*   **Payload:**
    ```json
    {
      "session": {
        "id": 15,
        "consumer_id": 12,
        "provider_id": 42,
        "created_at": "2026-07-20T13:45:00.000000Z",
        "updated_at": "2026-07-20T13:45:00.000000Z"
      },
      "senderData": {
        "id": 12,
        "name": "Amit Kumar",
        "profile_photo": "users/12/profile_photo/1.jpg"
      }
    }
    ```

### 3.2 `ChatAssistanceMessageSent`
Fired to the recipient when a new message is sent.
*   **Event Name:** `ChatAssistanceMessageSent`
*   **Payload:**
    ```json
    {
      "messageData": {
        "id": 182,
        "chat_assistance_session_id": 15,
        "sender_id": 12,
        "receiver_id": 42,
        "message": "Hello",
        "attachment_url": null,
        "type": "text",
        "is_read": false,
        "is_delivered": false,
        "call_session_id": null,
        "created_at": "2026-07-20T13:45:05.000000Z",
        "updated_at": "2026-07-20T13:45:05.000000Z"
      },
      "receiverId": 42
    }
    ```

### 3.3 `ChatAssistanceMessageStatusUpdated`
Fired to the sender when the recipient updates message status (e.g. read/delivered receipt).
*   **Event Name:** `ChatAssistanceMessageStatusUpdated`
*   **Payload:**
    ```json
    {
      "messageIds": [182],
      "status": "seen",
      "receiverId": 12,
      "sessionId": 15,
      "updatedBy": 42,
      "timestamp": "2026-07-20T13:45:10.000000Z"
    }
    ```

### 3.4 `ChatAssistanceLimitReached`
Fired to the astrologer's private channel when they exhaust their daily reply limit.
*   **Event Name:** `ChatAssistanceLimitReached`
*   **Payload:**
    ```json
    {
      "astrologerId": 42,
      "message": "Daily reply limit reached."
    }
    ```
