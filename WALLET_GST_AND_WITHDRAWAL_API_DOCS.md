# 📑 API Documentation: Wallet GST System, Astrologer Withdrawals & Invoicing

> **For Frontend Developers (Flutter, React Native, Web, Admin Dashboard)**  
> **Base API URL:** `https://your-domain.com/api/v1` (or local `http://127.0.0.1:8000/api/v1`)  
> **Standard Headers:**
> ```http
> Accept: application/json
> Content-Type: application/json
> Authorization: Bearer <SANCTUM_TOKEN>
> ```

---

## 📑 Table of Contents
1. [User (Consumer) Wallet & GST Recharge](#1-user-consumer-wallet--gst-recharge)
   - `POST /user/wallet/topup` — Initiate Top-up with GST Breakdown
   - `POST /user/wallet/verify-topup` — Verify Payment & Credit Base Amount
   - `GET /user/wallet/transactions/{id}/invoice` — Download Tax Invoice PDF
2. [Astrologer (Provider) Wallet, Dynamic Tax & Withdrawals](#2-astrologer-provider-wallet-dynamic-tax--withdrawals)
   - `GET /astrologer/wallet` — Wallet Summary & Balance
   - `GET /astrologer/wallet/withdrawal-config` — Fetch Dynamic GST Tax Limits & Config
   - `POST /astrologer/wallet/withdraw` — Request Payout Withdrawal with Tax Deduction
   - `GET /astrologer/wallet/withdrawals` — Withdrawal History List
   - `GET /astrologer/wallet/withdrawals/{id}/receipt` — Download Payout Tax Advice Receipt PDF
   - `GET /astrologer/wallet/weekly-rankings` — Astrologer Earnings Leaderboard
3. [Astrologer Profile Update (GSTIN Support)](#3-astrologer-profile-update-gstin-support)
   - `POST/PUT /astrologer/profile` — Profile Update with GSTIN
4. [Financial & Accounting Rules for UI Display](#4-financial--accounting-rules-for-ui-display)

---

# 1. User (Consumer) Wallet & GST Recharge

### 1.1 Initiate Wallet Top-up (Create Razorpay Order with GST)
* **Endpoint:** `POST /api/v1/user/wallet/topup`
* **Auth:** Required (`auth:sanctum`, `user`)
* **Description:** User recharges their wallet. When GST is enabled (e.g. 18%), the payment gateway order is created for `Base + 18% GST`. The UI should display the exact breakdown before redirecting to Razorpay gateway.

#### Request Body
```json
{
  "amount": 500
}
```
*(Note: `amount` represents the Base Amount the user wants to add to their wallet).*

#### Success Response (`200 OK`)
```json
{
  "status": "success",
  "message": "Order created successfully",
  "data": {
    "order_id": "order_Px123456789abc",
    "amount": 59000,
    "currency": "INR",
    "key": "rzp_test_xxxxxxxxx",
    "name": "Astology Services",
    "description": "Wallet Recharge of ₹500 (incl. GST ₹90.00)",
    "prefill": {
      "name": "Rahul Sharma",
      "email": "rahul@example.com",
      "contact": "9876543210"
    },
    "tax_breakdown": {
      "base_amount": 500,
      "gst_enabled": true,
      "gst_percent": 18,
      "gst_amount": 90,
      "total_payable": 590,
      "credit_to_wallet": 500
    }
  }
}
```

---

### 1.2 Verify Top-up Payment & Generate Invoice
* **Endpoint:** `POST /api/v1/user/wallet/verify-topup`
* **Auth:** Required (`auth:sanctum`, `user`)
* **Description:** Called immediately after Razorpay checkout succeeds. Safely credits strictly the **`base_amount`** (₹500) to the wallet and generates a legal GST Tax Invoice.

#### Request Body
```json
{
  "razorpay_order_id": "order_Px123456789abc",
  "razorpay_payment_id": "pay_Qz987654321xyz",
  "razorpay_signature": "4a76986e10b14c3e80c98f98c4a9235e1654..."
}
```

#### Success Response (`200 OK`)
```json
{
  "status": "success",
  "message": "Payment verified successfully",
  "data": {
    "balance": 1500.00,
    "credited_amount": 500.00,
    "total_paid": 590.00,
    "gst_amount": 90.00,
    "invoice_number": "INV-REC-20260821-000042",
    "invoice_url": "https://your-domain.com/api/v1/user/wallet/transactions/42/invoice",
    "transaction_id": 42
  }
}
```

---

### 1.3 Download Tax Invoice PDF
* **Endpoint:** `GET /api/v1/user/wallet/transactions/{id}/invoice`
* **Auth:** Required (`auth:sanctum`, `user`)
* **Description:** Downloads or streams the computer-generated GST Tax Invoice PDF.
* **Response:** PDF file download (`application/pdf`, filename `inv_rec_20260821_000042.pdf`).

---

# 2. Astrologer (Provider) Wallet, Dynamic Tax & Withdrawals

### 2.1 Get Astrologer Wallet Summary
* **Endpoint:** `GET /api/v1/astrologer/wallet`
* **Auth:** Required (`auth:sanctum`, `astrologer`)
* **Description:** Retrieves real-time balance, pending withdrawals lock, total withdrawn, lifetime earnings, and leaderboard rank.

#### Success Response (`200 OK`)
```json
{
  "status": "success",
  "message": "Wallet details fetched successfully",
  "data": {
    "balance": 8500.00,
    "available_balance": 7500.00,
    "pending_withdrawals": 1000.00,
    "total_withdrawn": 25000.00,
    "total_earnings": 33500.00,
    "rank": 4,
    "currency": "INR"
  }
}
```

---

### 2.2 Get Dynamic Withdrawal Configuration & Tax Limits
* **Endpoint:** `GET /api/v1/astrologer/wallet/withdrawal-config`
* **Auth:** Required (`auth:sanctum`, `astrologer`)
* **Description:** Call this API on loading the Withdrawal screen. Frontend uses this to render minimum withdrawal limits, GST tax preview notes, and bank account statuses.

#### Success Response (`200 OK`)
```json
{
  "status": "success",
  "message": "Withdrawal configuration fetched successfully",
  "data": {
    "current_balance": 8500.00,
    "available_balance": 7500.00,
    "pending_withdrawals": 1000.00,
    "min_withdrawal_amount": 500.00,
    "gst_enabled": true,
    "gst_withdrawal_rate": 18.00,
    "min_withdrawal_gst_threshold": 0.00,
    "has_verified_bank_account": true,
    "astrologer_gst_number": "07AAAAA0000A1Z5"
  }
}
```

---

### 2.3 Request Payout Withdrawal with Dynamic Tax Deduction
* **Endpoint:** `POST /api/v1/astrologer/wallet/withdraw`
* **Auth:** Required (`auth:sanctum`, `astrologer`)
* **Description:** Submits a payout request. Deducts gross amount from available balance with concurrency protection and generates payout breakdown.

#### Request Body
```json
{
  "amount": 1180
}
```

#### Success Response (`200 OK`)
```json
{
  "status": "success",
  "message": "Withdrawal request submitted successfully",
  "data": {
    "transaction_id": 89,
    "invoice_number": "INV-WD-20260821-000089",
    "receipt_url": "https://your-domain.com/api/v1/astrologer/wallet/withdrawals/89/receipt",
    "gross_debited": 1180.00,
    "gst_deducted": 180.00,
    "gst_rate": 18.00,
    "net_payout_amount": 1000.00,
    "status": "pending",
    "remaining_balance": 7320.00
  }
}
```

#### Error Responses
- `422 Unprocessable Content`:
  ```json
  {
    "status": "error",
    "message": "Insufficient available wallet balance. Requested: ₹1500.00, Available: ₹500.00 (Pending withdrawals: ₹1000.00)"
  }
  ```
- `403 Forbidden`:
  ```json
  {
    "status": "error",
    "message": "Please add and set a default bank account before requesting withdrawal."
  }
  ```

---

### 2.4 Get Withdrawal History
* **Endpoint:** `GET /api/v1/astrologer/wallet/withdrawals`
* **Auth:** Required (`auth:sanctum`, `astrologer`)

#### Success Response (`200 OK`)
```json
{
  "status": "success",
  "message": "Withdrawal history fetched successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 89,
        "amount": 1180.00,
        "base_amount": 1000.00,
        "gst_percent": 18.00,
        "gst_amount": 180.00,
        "total_amount": 1180.00,
        "invoice_number": "INV-WD-20260821-000089",
        "receipt_url": "https://your-domain.com/api/v1/astrologer/wallet/withdrawals/89/receipt",
        "status": "pending",
        "created_at": "2026-08-21T07:15:00.000000Z"
      }
    ],
    "total": 1
  }
}
```

---

### 2.5 Download Payout Tax Advice Receipt PDF
* **Endpoint:** `GET /api/v1/astrologer/wallet/withdrawals/{id}/receipt`
* **Auth:** Required (`auth:sanctum`, `astrologer`)
* **Description:** Streams or downloads the official TDS / GST payout advice document.
* **Response:** PDF file download (`application/pdf`).

---

### 2.6 Weekly Leaderboard Rankings
* **Endpoint:** `GET /api/v1/astrologer/wallet/weekly-rankings`
* **Auth:** Required (`auth:sanctum`, `astrologer`)

#### Success Response (`200 OK`)
```json
{
  "status": "success",
  "message": "Weekly rankings fetched successfully",
  "data": {
    "my_rank": 4,
    "my_weekly_earnings": 14200.00,
    "rankings": [
      {
        "rank": 1,
        "astrologer_id": 12,
        "name": "Acharya Dev",
        "profile_photo": "https://your-domain.com/storage/profiles/dev.jpg",
        "weekly_earnings": 32000.00
      }
    ]
  }
}
```

---

# 3. Astrologer Profile Update (GSTIN Support)

### 3.1 Update Astrologer Profile
* **Endpoint:** `POST /api/v1/astrologer/profile` (or `PUT`)
* **Auth:** Required (`auth:sanctum`, `astrologer`)
* **Content-Type:** `multipart/form-data` or `application/json`

#### Request Fields
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `name` / `full_name` | string | Optional | Full name |
| `gst_number` | string | Optional | **15-character statutory GSTIN** (e.g. `07AAAAA0000A1Z5`) |
| `years_of_experience`| numeric| Optional | 0 to 100 |
| `areas_of_expertise` | array/csv| Optional | `["Vedic", "Tarot"]` or `"Vedic,Tarot"` |
| `languages` | array/csv| Optional | `["English", "Hindi"]` |
| `date_of_birth` | date | Optional | `YYYY-MM-DD` (Must be 18+ years old) |
| `bio` | string | Optional | Bio text (max 2000 chars) |
| `city` | string | Optional | City name |
| `country` | string | Optional | Country name |
| `profile_photo` | image file | Optional | Max 5MB (jpg, png, webp) |
| `id_proof` | file | Optional | Max 5MB (pdf, jpg, png) |
| `certificate` | file | Optional | Max 5MB (pdf, jpg, png) |

#### Example Request Body
```json
{
  "name": "Acharya Vashisth",
  "gst_number": "07AAAAA0000A1Z5",
  "years_of_experience": 8,
  "areas_of_expertise": ["Vedic Astrology", "Vastu", "Kundli"],
  "languages": ["English", "Hindi"],
  "date_of_birth": "1992-05-14",
  "bio": "Experienced Vedic astrologer providing clear life guidance."
}
```

#### Success Response (`200 OK`)
```json
{
  "status": "success",
  "message": "Profile updated successfully",
  "data": {
    "id": 5,
    "name": "Acharya Vashisth",
    "gst_number": "07AAAAA0000A1Z5",
    "years_of_experience": 8,
    "areas_of_expertise": ["Vedic Astrology", "Vastu", "Kundli"],
    "languages": ["English", "Hindi"],
    "date_of_birth": "1992-05-14",
    "status": "approved"
  }
}
```

---

# 4. Financial & Accounting Rules for UI Display

1. **User Recharge Formula**:
   - `Base Amount` + `18% GST` = `Total Payable on Gateway`
   - *Example:* User selects ₹100 recharge → Total on Gateway is ₹118 → **Wallet balance credited = ₹100 strictly**.
2. **Astrologer Withdrawal Formula**:
   - `Net Payout` = `Requested Gross / 1.18`
   - `GST Deducted` = `Requested Gross - Net Payout`
   - *Example:* Astrologer requests ₹1180 → Debited from wallet = ₹1180 → **Bank receives = ₹1000, Tax deducted = ₹180**.
3. **Available Balance Calculation**:
   - `Available Balance` = `Current Balance - Pending Withdrawal Requests`.
   - UI should disable the "Withdraw" button if `available_balance < min_withdrawal_amount`.
