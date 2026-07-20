# Flutter Developer Copy-Paste Prompt (For AI Coding Agents)

Copy and paste the prompt below directly into your AI coding agent (e.g. Cursor, GitHub Copilot, Gemini, Claude) inside your Flutter/Android project to implement the frontend integration:

```markdown
# TASK: Implement Chat Assistance & Consultation Chat Continuity in Flutter

We have updated the backend consultation and chat assistance API pipelines. You need to update the Flutter app code (API service providers, state models, chat controllers, and UI views) to support:
1. Idempotent WhatsApp-style Chat Assistance (persistent session).
2. Chat history continuity across multiple Consultation Package session IDs.
3. Astrologer history integrations.

---

## 1. IMPLEMENT CHAT ASSISTANCE SYSTEM (FREE HELP THREAD)

### A. Persistent Session & Idempotency
- When initiating Chat Assistance via `POST /api/v1/chat-assistance/initiate`, the backend returns a single persistent session ID per consumer-astrologer pair. Always reuse this `session_id` in subsequent API requests.
- Endpoint: `/api/v1/chat-assistance/initiate` (Payload: `{ "provider_id": int, "call_session_id": int? }`)

### B. Message & Media Retrieval
- Endpoint to fetch messages: `GET /api/v1/chat-assistance/{sessionId}/messages?page=1`
- The backend returns a paginated list containing `chat_assistance_session_id` at the root of the data.
- **Media Urls:** The backend returns clean relative paths for images and attachments (e.g., `users/38/profile_photo/xyz.jpg`). The Flutter client MUST prefix this path with your configured Base URL + `/storage/` to render images.

### C. Synchronize Message Statuses (Delivered / Seen)
- Call `POST /api/v1/chat-assistance/{sessionId}/sync-status` when messages are delivered or read in the viewport.
- Payload:
  ```json
  {
    "status": "seen", // or "delivered"
    "message_ids": [182, 183]
  }
  ```

### D. WebSocket Event Listeners
Subscribe to the Laravel Echo private channel **`private-user.<my_user_id>`** and register handlers for these broadcast events:
- **`ChatAssistanceInitiated`**: When the session starts.
- **`ChatAssistanceMessageSent`**: When a new message is received. (Add it to your local message list/state).
- **`ChatAssistanceMessageStatusUpdated`**: When status updates (delivered/seen) are received. Update message status ticks in your UI.
- **`ChatAssistanceLimitReached`**: For astrologers, when they hit their daily reply limits. Display an alert overlay: "Daily message reply limit reached."

---

## 2. IMPLEMENT CONVERSATION CONTINUITY (PAID/PACKAGE SESSIONS)

### The Problem:
When a user switches between package chats and calls, the backend creates a fresh session record (new `chat_session_id`) each time to isolate billing and timers. If the Flutter app only queries messages matching the active `chat_session_id`, the user gets a blank chat screen whenever a new session starts.

### The Fix:
- Modify the Chat Screen Controller: When rendering the chat view, do NOT filter messages strictly by the active `chat_session_id`.
- **Query Strategy:** Load messages based on the User Pair conversation history (all messages exchanged between `consumer_id` and `provider_id` sorted by `created_at` timestamp).
- **Local Persistence:** If using a local SQLite/Hive database, insert new incoming messages into a single unified conversation thread for the peer user, regardless of changes in the session ID.

---

## 3. ASTROLOGER HISTORY INTEGRATION (ACTIVE ASSISTANCE SHORTCUT)

The backend now returns `chat_assistance_session_id` in the astrologer call and chat list endpoints:
- `GET /api/v1/call/sessions/astrologer`
- `GET /api/v1/chat/sessions/astrologer`

Check each session item:
- If `chat_assistance_session_id` is NOT null, display a quick-navigation button/icon on the history item list row.
- Clicking the button must route the astrologer directly to the Free Chat Assistance room with that consumer using the provided ID.
```
