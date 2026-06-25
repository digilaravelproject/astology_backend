# Astrologer Performance API Documentation

This API endpoint provides dynamic performance statistics for the authenticated astrologer. It calculates profile health metrics, availability logs, and loyal user conversion rates based on chat/call session histories.

## Endpoint Details

- **URL**: `/api/v1/astrologer/performance`
- **Method**: `GET`
- **Headers**:
  - `Authorization: Bearer <token>`
  - `Accept: application/json`

## Response Format

### Success Response (`200 OK`)

```json
{
  "status": "success",
  "message": "Astrologer performance data retrieved successfully.",
  "data": {
    "badge_type": "Rising Star",
    "profile_health": {
      "date": "25 June 2026",
      "total_sessions": 5,
      "missed_sessions": 2,
      "revenue_loss": 350.00,
      "missed_calls": 1,
      "missed_chats": 1,
      "loyal_users": 3
    },
    "availability": {
      "available_mins": {
        "today": 480,
        "seven_days": 3360,
        "thirty_days": 14400
      },
      "busy_mins": {
        "today": 120,
        "seven_days": 840,
        "thirty_days": 3600
      }
    },
    "loyal_user_conversion": {
      "conversion_percentage": 22.4,
      "total_users": 500,
      "loyal_users": 112,
      "loyal_user_level": 2
    }
  }
}
```

### Error Response (`401 Unauthorized`)

```json
{
  "message": "Unauthenticated."
}
```

### Error Response (`404 Not Found`)

```json
{
  "status": "error",
  "message": "Astrologer profile not found."
}
```

## Calculation Logics

1. **Badge Type**:
   - Determined by the astrologer's cumulative busy minutes:
     - `total_busy_minutes < 1000` -> **Rising Star**
     - `1000 <= total_busy_minutes < 5000` -> **Top Choice**
     - `total_busy_minutes >= 5000` -> **Celebrity**

2. **Today's Profile Health**:
   - `total_sessions`: Sum of call and chat sessions created today.
   - `missed_sessions`: Sum of calls (`missed`, `failed`, `rejected` status) and chats (`rejected` status) today.
   - `revenue_loss`: Potential revenue lost today computed as: `(missed_calls * call_rate * 5) + (missed_chats * chat_rate * 5)` (assumes 5 minutes standard duration).
   - `missed_calls` & `missed_chats`: Counts of today's failed/missed/rejected call and chat sessions respectively.
   - `loyal_users`: Count of unique customers who have completed $\ge 2$ sessions (chat or call) with this astrologer.

3. **Availability & Busy Minutes**:
   - **Busy Minutes**: Calculated as `sum(duration_seconds) / 60` for completed sessions in the respective period (Today, Last 7 Days, Last 30 Days).
   - **Available Minutes**: Derived from the recurring schedule slots in the astrologer's profile availability array for the corresponding time frame.

4. **Loyal User Conversion**:
   - `conversion_percentage`: `(Loyal Users / Total Unique Users) * 100`.
   - `loyal_user_level`:
     - `loyal_users < 10` -> **Level 1**
     - `10 <= loyal_users < 50` -> **Level 2**
     - `50 <= loyal_users < 100` -> **Level 3**
     - `loyal_users >= 100` -> **Level 4**
