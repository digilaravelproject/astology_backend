# Quick CURL Commands Reference

## Login First (Get Token)

```bash
# Send OTP
curl -X POST "http://localhost/astrology/api/v1/user/send-otp" \
  -H "Content-Type: application/json" \
  -d '{"phone": "9876543210"}'

# Verify OTP (Development OTP = 1234)
curl -X POST "http://localhost/astrology/api/v1/user/verify-otp" \
  -H "Content-Type: application/json" \
  -d '{"phone": "9876543210", "otp": "1234"}'

# Copy the 'token' value from response for next commands
```

---

## 1️⃣ Get Following List

```bash
curl -X GET "http://localhost/astrology/api/v1/user/following" \
  -H "Authorization: Bearer TOKEN_HERE" \
  -H "Content-Type: application/json"
```

**Example with actual token:**

```bash
curl -X GET "http://localhost/astrology/api/v1/user/following" \
  -H "Authorization: Bearer 1|abcd1234efgh5678ijkl9012mnop3456" \
  -H "Content-Type: application/json"
```

**Response:**

```json
{
    "status": "success",
    "message": "Following list retrieved successfully.",
    "data": {
        "count": 2,
        "following": [
            {
                "astrologer_id": 1,
                "name": "Aarti Sharma",
                "email": "aarti@astrology.com",
                "years_of_experience": 10,
                "areas_of_expertise": ["Vedic Astrology"],
                "status": "approved",
                "followed_at": "2024-03-25T10:30:00Z"
            }
        ]
    }
}
```

---

## 2️⃣ Logout User

```bash
curl -X POST "http://localhost/astrology/api/v1/user/logout" \
  -H "Authorization: Bearer TOKEN_HERE" \
  -H "Content-Type: application/json"
```

**Example:**

```bash
curl -X POST "http://localhost/astrology/api/v1/user/logout" \
  -H "Authorization: Bearer 1|abcd1234efgh5678ijkl9012mnop3456" \
  -H "Content-Type: application/json"
```

**Response:**

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

---

## 3️⃣ Delete User Account

```bash
curl -X DELETE "http://localhost/astrology/api/v1/user/delete-account" \
  -H "Authorization: Bearer TOKEN_HERE" \
  -H "Content-Type: application/json"
```

**Example:**

```bash
curl -X DELETE "http://localhost/astrology/api/v1/user/delete-account" \
  -H "Authorization: Bearer 1|abcd1234efgh5678ijkl9012mnop3456" \
  -H "Content-Type: application/json"
```

**Response:**

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

---

## Alternative: Using Postman

### Headers (All Requests):

```
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json
```

### 1. Get Following

- **Method:** GET
- **URL:** http://localhost/astrology/api/v1/user/following
- **Body:** None

### 2. Logout

- **Method:** POST
- **URL:** http://localhost/astrology/api/v1/user/logout
- **Body:** None

### 3. Delete Account

- **Method:** DELETE
- **URL:** http://localhost/astrology/api/v1/user/delete-account
- **Body:** None

---

## Common Issues

### "Unauthenticated user" Error

- Make sure you have a valid token
- Use the Bearer token from login/verify-otp response
- Check token hasn't expired

### "Authenticated user not found" Error

- Token might be for an astrologer account, not user
- Try logging in again with correct phone number

### "An error occurred" Error

- Check server logs: `storage/logs/laravel.log`
- Ensure database connection is working
- Verify all tables exist

---

## Testing Tips

1. **Always get a fresh token first** before testing any endpoint
2. **Keep token in a variable** for easier testing:

    ```bash
    TOKEN="1|abcd1234efgh5678ijkl9012mnop3456"
    curl -X GET "http://localhost/astrology/api/v1/user/following" \
      -H "Authorization: Bearer $TOKEN"
    ```

3. **Use jq for pretty JSON output**:

    ```bash
    curl -X GET "http://localhost/astrology/api/v1/user/following" \
      -H "Authorization: Bearer $TOKEN" | jq .
    ```

4. **Delete account carefully** - This action is irreversible!

---

## What Gets Deleted with Account

✅ User profile  
✅ All authentication tokens  
✅ Wallet and transactions  
✅ Astrologer reviews by user  
✅ Following/follower relationships  
✅ Matrimony profiles  
✅ User notifications
