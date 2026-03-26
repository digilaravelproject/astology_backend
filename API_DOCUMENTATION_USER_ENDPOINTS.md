# User APIs Documentation

## Base URL

```
http://localhost/astrology/api/v1/user
```

---

## 1. Get Following List

**Endpoint:** `GET /following`

**Description:** Get list of all astrologers that the user is following.

**Authentication:** Required (Bearer token from login)

**Method:** GET

### Response Example:

```json
{
    "status": "success",
    "message": "Following list retrieved successfully.",
    "data": {
        "count": 2,
        "following": [
            {
                "astrologer_id": 1,
                "user_id": 5,
                "name": "Aarti Sharma",
                "email": "aarti@astrology.com",
                "phone": "9876543210",
                "profile_photo": null,
                "years_of_experience": 10,
                "areas_of_expertise": ["Vedic Astrology", "Numerology"],
                "languages": ["Hindi", "English"],
                "bio": "Expert astrologer with 10+ years experience",
                "status": "approved",
                "followed_at": "2024-03-25T10:30:00Z",
                "created_at": "2024-03-25T10:30:00Z"
            },
            {
                "astrologer_id": 2,
                "user_id": 6,
                "name": "Vikram Joshi",
                "email": "vikram@astrology.com",
                "phone": "9876543211",
                "profile_photo": null,
                "years_of_experience": 8,
                "areas_of_expertise": ["Tarot Reading", "Career Guidance"],
                "languages": ["English"],
                "bio": "Specialized in career and relationship counseling",
                "status": "approved",
                "followed_at": "2024-03-24T15:45:00Z",
                "created_at": "2024-03-24T15:45:00Z"
            }
        ]
    }
}
```

### CURL Command:

```bash
curl -X GET "http://localhost/astrology/api/v1/user/following" \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -H "Content-Type: application/json"
```

### CURL with Real Token Example:

```bash
curl -X GET "http://localhost/astrology/api/v1/user/following" \
  -H "Authorization: Bearer 1|abcd1234efgh5678ijkl9012mnop3456" \
  -H "Content-Type: application/json"
```

---

## 2. Logout User

**Endpoint:** `POST /logout`

**Description:** Logout the user by revoking all authentication tokens. User will not be able to use the old token after logout.

**Authentication:** Required (Bearer token from login)

**Method:** POST

### Request Body:

No body required

### Response Example:

```json
{
    "status": "success",
    "message": "Logged out successfully.",
    "data": {
        "user_id": 10,
        "logged_out_at": "2024-03-25T11:20:30Z"
    }
}
```

### CURL Command:

```bash
curl -X POST "http://localhost/astrology/api/v1/user/logout" \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -H "Content-Type: application/json"
```

### CURL with Real Token Example:

```bash
curl -X POST "http://localhost/astrology/api/v1/user/logout" \
  -H "Authorization: Bearer 1|abcd1234efgh5678ijkl9012mnop3456" \
  -H "Content-Type: application/json"
```

---

## 3. Delete User Account

**Endpoint:** `DELETE /delete-account`

**Description:** Permanently delete the user account and all associated data from the database. This action is irreversible!

**Authentication:** Required (Bearer token from login)

**Method:** DELETE

**Important Note:** When a user account is deleted, the following records are also permanently removed:

- User profile and authentication tokens
- Wallet and wallet transactions
- Astrologer reviews by the user
- Following/follower relationships (astrologer community records)
- Matrimony profiles
- All notifications for the user

### Request Body:

No body required

### Response Example:

```json
{
    "status": "success",
    "message": "Account deleted successfully. All your data has been removed.",
    "data": {
        "user_id": 10,
        "deleted_at": "2024-03-25T11:25:45Z"
    }
}
```

### Error Response:

```json
{
    "status": "error",
    "message": "An error occurred while deleting the account.",
    "error_details": "Error message details"
}
```

### CURL Command:

```bash
curl -X DELETE "http://localhost/astrology/api/v1/user/delete-account" \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -H "Content-Type: application/json"
```

### CURL with Real Token Example:

```bash
curl -X DELETE "http://localhost/astrology/api/v1/user/delete-account" \
  -H "Authorization: Bearer 1|abcd1234efgh5678ijkl9012mnop3456" \
  -H "Content-Type: application/json"
```

---

## How to Get Auth Token

First, you need to login/verify OTP to get the auth token:

### Step 1: Send OTP

```bash
curl -X POST "http://localhost/astrology/api/v1/user/send-otp" \
  -H "Content-Type: application/json" \
  -d '{"phone": "9876543210"}'
```

### Step 2: Verify OTP

```bash
curl -X POST "http://localhost/astrology/api/v1/user/verify-otp" \
  -H "Content-Type: application/json" \
  -d '{"phone": "9876543210", "otp": "1234"}'
```

The response will contain the auth token (in development, OTP is hardcoded as "1234"):

```json
{
    "status": "success",
    "message": "OTP verified successfully.",
    "data": {
        "user_id": 10,
        "token": "1|abcd1234efgh5678ijkl9012mnop3456",
        "token_type": "Bearer"
    }
}
```

Use the `token` value in the `Authorization: Bearer` header for authenticated requests.

---

## Testing Flow

### Complete Testing Sequence:

```bash
# 1. Send OTP
curl -X POST "http://localhost/astrology/api/v1/user/send-otp" \
  -H "Content-Type: application/json" \
  -d '{"phone": "9876543210"}'

# 2. Verify OTP (use "1234" in development)
curl -X POST "http://localhost/astrology/api/v1/user/verify-otp" \
  -H "Content-Type: application/json" \
  -d '{"phone": "9876543210", "otp": "1234"}'

# Save the token from response: YOUR_AUTH_TOKEN

# 3. Get Following List
curl -X GET "http://localhost/astrology/api/v1/user/following" \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -H "Content-Type: application/json"

# 4. Logout
curl -X POST "http://localhost/astrology/api/v1/user/logout" \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -H "Content-Type: application/json"

# 5. Delete Account (be careful with this!)
curl -X DELETE "http://localhost/astrology/api/v1/user/delete-account" \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -H "Content-Type: application/json"
```

---

## Error Responses

### Unauthorized (401)

```json
{
    "status": "error",
    "message": "Unauthenticated user."
}
```

### Not Found (404)

```json
{
    "status": "error",
    "message": "Authenticated user not found or not a regular user."
}
```

### Server Error (500)

```json
{
    "status": "error",
    "message": "An error occurred while processing your request."
}
```

---

## Implementation Details

### Files Modified:

1. **app/Http/Controllers/Api/UserAuthController.php**
    - Added `getFollowing()` method
    - Added `logout()` method
    - Added `deleteAccount()` method

2. **routes/api.php**
    - Added route for GET `/user/following`
    - Added route for POST `/user/logout`
    - Added route for DELETE `/user/delete-account`

### Features:

- ✅ Get following list with astrologer details
- ✅ Logout user with token revocation
- ✅ Delete user account with cascade deletion of all related records
- ✅ Transaction-based operations for data integrity
- ✅ Proper error handling and logging
- ✅ Notification support for user actions

---

## Database Cascade Deletions

When a user account is deleted, the following tables are cleaned via foreign key constraints:

```
users (deleted)
├── wallets (cascade delete)
│   └── wallet_transactions (cascade delete)
├── astrologer_reviews (cascade delete)
├── astrologer_communities (cascade delete)
├── matrimony_profiles (cascade delete)
├── app_notifications (cascade delete)
└── tokens (cascade delete)
```
