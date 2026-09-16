# Live Session Call Feature Specification (Technical API & WebRTC Signaling)

This document provides a 100% production-accurate technical specification for the **Live Session Call Feature** (Viewer-to-Astrologer WebRTC Audio Consultations during a Live Broadcast). It covers REST signaling endpoints, WebSockets (Reverb/Broadcasting), integration with LiveKit, and per-minute billing.

---

## 1. Feature Architecture & Lifecycle Flow

The calling system uses **Laravel Broadcasting as the WebRTC Signaling Plane** for the 1-on-1 audio bridge, while the overall Live Stream relies on **LiveKit**. The audio mixing of the WebRTC track into the LiveKit broadcast is handled natively by the client applications.

### Sequence Diagram
```mermaid
sequenceDiagram
    participant C as Consumer (User App)
    participant API as Laravel Backend
    participant WS as WebSocket/Reverb
    participant P as Provider (Astro App)
    participant LK as LiveKit (Broadcast)

    Note over C,P: Both users are in a LiveKit Room (live_session_id=10)
    Note over C: User creates RTCPeerConnection & SDP Offer
    C->>API: POST /api/call/initiate (provider_id, offer, live_session_id=10)
    API-->>WS: Broadcast `CallInitiated` (with SDP Offer, session_type="live")
    WS-->>P: Deliver `CallInitiated` on `private-user.{providerId}`
    API->>P: FCM High-Priority Push Notification (`action: RING`)

    alt Astrologer Accepts within 60s
        Note over P: Astrologer receives Call popup over Live Stream
        P->>API: POST /api/call/{sessionId}/accept (answer)
        API-->>WS: Broadcast `CallAccepted` (with SDP Answer)
        WS-->>C: Deliver `CallAccepted` on `private-user.{consumerId}`
        
        rect rgb(35, 35, 35)
            note over C,P: ICE Candidate Trickle Exchange (Standard WebRTC)
            C->>API: POST /api/call/{sessionId}/ice-candidate (candidate)
            API-->>WS: Broadcast `IceCandidateSent` -> P
            P->>API: POST /api/call/{sessionId}/ice-candidate (candidate)
            API-->>WS: Broadcast `IceCandidateSent` -> C
            Note over C,P: P2P Audio Media Connected
            Note over P: Astrologer App mixes WebRTC audio into LiveKit Local Audio Track
        end

        rect rgb(25, 25, 25)
            note over API: Call Active - CallBillingTickJob runs every 60s
            note over API: Exclusivity lock active (No other viewer can call)
        end

        C->>API: POST /api/call/{sessionId}/end
        API-->>WS: Broadcast `CallEnded` (with Billing details) to both
        WS-->>P: Deliver `CallEnded` -> P closes WebRTC, stays in LiveKit
        WS-->>C: Deliver `CallEnded` -> C closes WebRTC, stays in LiveKit
    else Astrologer Rejects or Viewer Cancels
        note over API,WS: Handled identically to normal calls (CallDismissed)
    end
```

---

## 2. REST API Endpoints (Categorized by Role)

---

### A. Consumer (User) Exclusive APIs

#### A.1 Initiate Live Call
- **Method & Route:** `POST /api/call/initiate`
- **Intent:** Viewer requests an audio call with the astrologer during a live session.
- **Pre-Conditions & Validation Rules:**
  - `live_session_id` must be passed and point to an `ongoing` Live Session belonging to the `provider_id`.
  - **Exclusivity Lock:** The astrologer must not already be in an active `CallSession`.
  - **Wallet Balance Rule:** Consumer must have a minimum wallet balance equal to **at least 5 minutes** of consultation (`balance >= rate_per_minute * 5`).
- **Request Payload (JSON):**
  ```json
  {
    "provider_id": 123,
    "live_session_id": 10,
    "offer": "v=0\r\no=- 4210741285 2 IN IP4 127.0.0.1..."
  }
  ```
- **Success Response (HTTP 200):**
  *Note that `session_type` is returned as `"live"` and `live_session_id` is populated.*
  ```json
  {
    "success": true,
    "message": "Call initiated successfully",
    "data": {
      "session": {
        "id": 89,
        "consumer_id": 12,
        "provider_id": 123,
        "session_type": "live",
        "live_session_id": 10,
        "call_type": "audio",
        "status": "initiated",
        "rate_per_minute": 20.00
      }
    }
  }
  ```

---

### B. Provider (Astrologer) Exclusive APIs

#### B.1 Accept Live Call
- **Method & Route:** `POST /api/call/{sessionId}/accept`
- **Intent:** Astrologer answers the incoming live call.
- **Rules:**
  - Astrologer is marked as `busy` for incoming calls, preventing any other viewers from calling in.
  - Schedules `CallBillingTickJob` for automatic per-minute wallet debiting.
- **Request Payload (JSON):**
  ```json
  {
    "answer": "v=0\r\no=- 5321876412 2 IN IP4 127.0.0.1..."
  }
  ```

---

### C. Common APIs (Available to BOTH Consumer and Astrologer)

#### C.1 End Live Call Session
- **Method & Route:** `POST /api/call/{sessionId}/end`
- **Intent:** Either party hangs up the 1-on-1 audio call without leaving the LiveKit broadcast.
- **Financial Settlement Logic:**
  - Follows standard `PricingCalculatorService`.
  - Deducts final unbilled duration from the consumer's wallet.
- **Success Response (HTTP 200):** Same as normal call.

#### C.2 Get Current Active Call Session
- **Method & Route:** `GET /api/call/current-session`
- **Intent:** App resume check. If the app reloads during a live session, it can fetch this to realize there is an ongoing live call overlay to render.
- **Success Response (HTTP 200):**
  ```json
  {
    "success": true,
    "data": {
      "session": {
        "id": 89,
        "status": "ongoing",
        "session_type": "live",
        "live_session_id": 10
      },
      "session_type": "live"
    }
  }
  ```

---

## 3. Real-Time WebSockets & Broadcasting Events

Because the backend reuses the exact same WebRTC infrastructure as Normal Calls, **the socket events remain exactly the same.** No custom events (like `LiveSessionCallStarted`) are broadcasted to the global live room.

### `CallInitiated`
- **Channels:** `private-user.{providerId}`
- **Differences from Normal Call:** The payload will clearly indicate `session_type: "live"` and include `live_session_id: 10`.
- **Frontend Action:** The Astrologer App should intercept this. Seeing `session_type: "live"`, it should display a non-intrusive "Incoming Live Call" popup layered over the Live Broadcast, rather than jumping to a standalone Incoming Call screen.

### `CallEnded` / `CallDismissed`
- **Channels:** `private-user.{consumerId}`, `private-user.{providerId}`
- **Frontend Action:** When received, tear down the WebRTC `RTCPeerConnection` and remove the audio mix from LiveKit. Do **not** disconnect from the LiveKit room.

---

## 4. Frontend Implementation Guidelines (Client-Side Audio Mixing)

1. **Offer/Answer Flow:** Identical to standard 1-on-1 calls.
2. **UI Layering:** Since `session_type: "live"` is provided, the call UI should be an overlay (e.g., a small "Active Call" widget) inside the Live Session screen.
3. **Audio Mixing (Crucial):**
   - The Consumer (Viewer) will have an active WebRTC connection to the Astrologer.
   - The Astrologer's device will receive the Viewer's audio via WebRTC.
   - To ensure other viewers hear the caller, the Astrologer App must mix the incoming WebRTC audio track with the Astrologer's microphone input before publishing to LiveKit. (Alternatively, if played on the device speaker, the microphone will naturally pick it up, though software mixing is cleaner).
