# Price Increase System — Documentation

## Overview

Astrologers can request a rate increase (call or chat rate per minute) once they meet busy-minute thresholds defined by admin. Increases go through an **admin approval flow** — rates only change after admin approves.

---

## 1. Database Schema

### `price_increase_levels`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| name | string(255) | Display name (e.g. "Beginner", "Pro") |
| level_number | integer (unique) | Ordering (1, 2, 3...) |
| required_busy_minutes | integer | Minutes needed to unlock |
| max_increase_amount | decimal(10,2) | Max $ increase at this level |
| is_active | boolean (default: true) | Soft enable/disable |
| created_at | timestamp | |
| updated_at | timestamp | |

### `price_increase_requests`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Auto-increment |
| astrologer_id | bigint (FK → astrologers) | Who requested |
| level_id | bigint (FK → price_increase_levels) | Level at time of request |
| price_type | enum('call','chat') | Which rate to increase |
| old_price | decimal(10,2) | Current rate before increase |
| new_price | decimal(10,2) | Proposed rate after increase |
| increase_amount | decimal(10,2) | new_price - old_price |
| status | enum('pending','approved','rejected') | Current state |
| admin_remark | text (nullable) | Admin note on approve/reject |
| approved_at | timestamp (nullable) | When approved |
| rejected_at | timestamp (nullable) | When rejected |
| created_at | timestamp | |
| updated_at | timestamp | |

Index: `(astrologer_id, status)`

---

## 2. Business Logic (Service Layer)

File: `app/Services/PriceIncreaseService.php`

### `getTotalBusyMinutesAttribute` (Astrologer model)

Computed on-the-fly — **no stored column**:

```
total_busy_minutes = (
    SUM(call_sessions.duration_seconds WHERE status IN ('completed','approved')) +
    SUM(chat_sessions.duration_seconds WHERE status IN ('completed','approved'))
) / 60
```

Session tables use `provider_id = users.id` (not astrologer.id). The Astrologer model's `user_id` is used to join.

### `getStatus(Astrologer $astrologer): array`

1. Calculate `total_busy_minutes` from CallSession + ChatSession.
2. Find **current level**: highest active level where `required_busy_minutes <= total_busy_minutes`.
3. Find **next level**: first active level where `required_busy_minutes > total_busy_minutes`.
4. Check for existing pending request.
5. Return full status object.

### `requestIncrease(Astrologer $astrologer, string $priceType): PriceIncreaseRequest`

1. Validate: astrologer has a current level (eligible).
2. Validate: no pending request already exists.
3. Validate: price type is `call` or `chat`.
4. Calculate increase:
   - `increase_amount = min(level.max_increase_amount, old_price * 0.20)`
   - `new_price = old_price + increase_amount`
5. **DB::transaction()**: Create `PriceIncreaseRequest` with `status = 'pending'`.

### `approveRequest(PriceIncreaseRequest $request, ?string $remark): PriceIncreaseRequest`

1. Validate: request status is `pending`.
2. **DB::transaction()**:
   - Update astrologer's `chat_rate_per_minute` or `call_rate_per_minute` to `new_price`.
   - Set request `status = 'approved'`, `approved_at = now()`.

### `rejectRequest(PriceIncreaseRequest $request, ?string $remark): PriceIncreaseRequest`

1. Validate: request status is `pending`.
2. **DB::transaction()**:
   - Set request `status = 'rejected'`, `rejected_at = now()`.

---

## 3. API Endpoints (Astrologer App)

All under: `/api/v1/astrologer/`  
Middleware: `auth:sanctum`, `throttle:tiered`

### 3.1 GET `/price-increase/status`

Get eligibility, current level, next level, pending request.

**Response:**

```json
{
  "status": "success",
  "message": "Price increase status retrieved successfully.",
  "data": {
    "total_busy_minutes": 1520.5,
    "current_level": {
      "id": 1,
      "name": "Beginner",
      "level_number": 1,
      "required_busy_minutes": 1000,
      "max_increase_amount": 5.00
    },
    "next_level": {
      "id": 2,
      "name": "Intermediate",
      "level_number": 2,
      "required_busy_minutes": 5000,
      "max_increase_amount": 10.00
    },
    "current_rates": {
      "chat_rate_per_minute": 15.00,
      "call_rate_per_minute": 20.00
    },
    "pending_request": {
      "id": 5,
      "price_type": "chat",
      "old_price": 15.00,
      "new_price": 18.00,
      "increase_amount": 3.00,
      "level_name": "Beginner",
      "created_at": "2026-06-09 12:30:00"
    },
    "can_request": false
  }
}
```

- `can_request`: `true` only when `current_level !== null && pending_request === null`.

### 3.2 POST `/price-increase/request`

Submit a new price increase request.

**Payload:**

```json
{
  "price_type": "chat"
}
```

`price_type` must be `"call"` or `"chat"`.

**Success Response (201):**

```json
{
  "status": "success",
  "message": "Price increase request submitted successfully.",
  "data": {
    "id": 6,
    "price_type": "chat",
    "old_price": 15.00,
    "new_price": 18.00,
    "increase_amount": 3.00,
    "status": "pending",
    "created_at": "2026-06-09 12:35:00"
  }
}
```

**Error Responses:**

- `422` — Validation failed (invalid price_type).
- `400` — Not eligible / already has pending request.

### 3.3 GET `/price-increase/history`

Get all past requests for the authenticated astrologer.

**Response:**

```json
{
  "status": "success",
  "message": "Price increase history retrieved successfully.",
  "data": [
    {
      "id": 1,
      "level_name": "Beginner",
      "price_type": "chat",
      "old_price": 15.00,
      "new_price": 18.00,
      "increase_amount": 3.00,
      "status": "approved",
      "admin_remark": "Approved based on performance.",
      "created_at": "2026-06-01 10:00:00",
      "approved_at": "2026-06-02 14:30:00",
      "rejected_at": null
    }
  ]
}
```

---

## 4. Admin Panel (Web)

All under: `/admin` prefix with `admin` middleware.

### 4.1 Price Increase Levels

| Method | Route | Controller Method | Description |
|--------|-------|-------------------|-------------|
| GET | `/price-increase-levels` | index | List all levels (paginated, searchable, filterable) |
| GET | `/price-increase-levels/create` | create | Show create form |
| POST | `/price-increase-levels` | store | Store new level |
| GET | `/price-increase-levels/{id}/edit` | edit | Show edit form |
| PUT | `/price-increase-levels/{id}` | update | Update level |
| POST | `/price-increase-levels/{id}/toggle-status` | toggleStatus | Toggle is_active |
| DELETE | `/price-increase-levels/{id}` | destroy | Delete (blocked if requests exist) |

**Form fields:** name, level_number, required_busy_minutes, max_increase_amount, is_active

**Validation rules:**
- `level_number`: required, integer, min:1, unique (except current on update)
- `max_increase_amount`: required, numeric, min:0

### 4.2 Price Increase Requests

| Method | Route | Controller Method | Description |
|--------|-------|-------------------|-------------|
| GET | `/price-increase-requests` | index | List all requests (paginated, searchable, filterable) |
| GET | `/price-increase-requests/{id}` | show | View request detail |
| POST | `/price-increase-requests/{id}/approve` | approve | Approve → updates astrologer rate |
| POST | `/price-increase-requests/{id}/reject` | reject | Reject |

**Filters available on index:**
- `search` — name/email of astrologer
- `status` — pending/approved/rejected
- `price_type` — call/chat

**Approve/Reject:** Both accept optional `admin_remark` field in POST body.

### 4.3 Views

- `price_increase_levels/index.blade.php` — Stats cards, filter console, table with actions
- `price_increase_levels/form.blade.php` — Shared create/edit form with quick info sidebar
- `price_increase_requests/index.blade.php` — Stats cards (total/pending/approved/rejected), filters, table
- `price_increase_requests/show.blade.php` — Detail view with pricing comparison, timeline, approve/reject inline forms with optional remark

All views extend `admin.layouts.app` and follow existing Tailwind CSS design patterns.

---

## 5. Admin Sidebar

Added under **Astrologers** section (toggle stays open when on these pages):

```
▶ Astrologers
  ├── All Astrologers
  ├── Performance
  ├── Ratings & Reviews
  ├── Community
  ├── Report Astrologer
  ├── Live Now
  ├── Gallery
  ├── Live Sessions
  ├── Default Pricing
  ├── Price Increase Levels       ← NEW
  └── Price Increase Requests     ← NEW
```

File: `resources/views/admin/layouts/app.blade.php`

---

## 6. Error Handling

Every controller method is wrapped in `try-catch`:
- Catches `\Exception`, logs via `Log::error()` with context.
- Admin: redirects back with `->with('error', message)`.
- API: returns standardized error JSON with appropriate HTTP code.
- Service layer: re-throws `RuntimeException` for business-logic errors, catches and wraps unexpected exceptions.

---

## 7. Key Business Rules

1. **Eligibility**: Astrologer must have busy minutes >= a level's `required_busy_minutes`.
2. **No duplicate pending**: Only 1 pending request allowed at a time.
3. **Increase cap**: `min(level.max_increase_amount, old_price * 20%)`.
4. **No direct update**: Prices are never changed directly by astrologer — only through admin approval.
5. **Level deletion blocked**: Cannot delete a level if it has associated requests.
6. **Busy minutes**: Computed on-the-fly (no sync issues), includes only `completed`/`approved` sessions.

---

## 8. How to Test

**Admin:**
1. Navigate to `/admin/login` → login as admin.
2. Go to Astrologers → Price Increase Levels → create Level 1 (1000 min, $5 max).
3. Go to Price Increase Requests → view pending requests.

**API (via Postman or app):**
1. Login as astrologer → get Bearer token.
2. `GET /api/v1/astrologer/price-increase/status` — check eligibility.
3. `POST /api/v1/astrologer/price-increase/request` with `{ "price_type": "chat" }`.
4. Admin approves → check astrologer rate updated in DB or profile.
5. `GET /api/v1/astrologer/price-increase/history` — see approved request.
