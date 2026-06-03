# Astrologer Wallet API Documentation

This document describes the API endpoints for the Astrologer Wallet system. All routes are authenticated via Laravel Sanctum and require the user to have an astrologer account.

---

## Base URL
All endpoints are prefixed with:
`https://yourdomain.com/api/v1/astrologer`

---

## Authentication Header
Include the Bearer token in the `Authorization` header:
`Authorization: Bearer <token>`
`Accept: application/json`

---

## Endpoints

### 1. Wallet Summary (Stats & Balance)
Retrieves the astrologer's total wallet balance, completed earnings metrics (today, weekly, monthly, 3-month), and their ranking based on total earnings.

* **Method:** `GET`
* **URL:** `/wallet`
* **Response (Success - 200 OK):**
  ```json
  {
      "status": "success",
      "data": {
          "total_balance": 1000.0,
          "today_earning": 300.0,
          "weekly_earning": 300.0,
          "monthly_earning": 600.0,
          "three_month_earning": 1000.0,
          "rank": 2
      }
  }
  ```

---

### 2. Earning History
Retrieves a paginated list of completed credit transactions (earnings from calls/chats).

* **Method:** `GET`
* **URL:** `/wallet/earnings`
* **Query Parameters:**
  - `filter` (Optional, string): Filter earnings by time.
    - Options: `today`, `weekly`, `monthly`, or omit for all earnings.
  - `page` (Optional, integer): Page number for pagination.
* **Response (Success - 200 OK):**
  ```json
  {
      "status": "success",
      "data": {
          "current_page": 1,
          "data": [
              {
                  "id": 4,
                  "wallet_id": 1,
                  "transaction_type": "credit",
                  "amount": "100.00",
                  "status": "completed",
                  "description": "Today credit",
                  "meta": null,
                  "created_at": "2026-06-15T12:00:00.000000Z",
                  "updated_at": "2026-06-15T12:00:00.000000Z"
              }
          ],
          "first_page_url": ".../api/v1/astrologer/wallet/earnings?page=1",
          "from": 1,
          "last_page": 1,
          "last_page_url": ".../api/v1/astrologer/wallet/earnings?page=1",
          "next_page_url": null,
          "path": ".../api/v1/astrologer/wallet/earnings",
          "per_page": 15,
          "prev_page_url": null,
          "to": 1,
          "total": 1
      }
  }
  ```

---

### 3. Withdrawal History
Retrieves a paginated list of debit transactions representing withdrawals.

* **Method:** `GET`
* **URL:** `/wallet/withdrawals`
* **Query Parameters:**
  - `page` (Optional, integer): Page number for pagination.
* **Response (Success - 200 OK):**
  ```json
  {
      "status": "success",
      "data": {
          "current_page": 1,
          "data": [
              {
                  "id": 8,
                  "wallet_id": 1,
                  "transaction_type": "debit",
                  "amount": "300.00",
                  "status": "pending",
                  "description": "Withdrawal Request",
                  "meta": {
                      "bank_account_id": 1,
                      "account_holder_name": "Vikram Sharma",
                      "bank_name": "State Bank of India",
                      "account_number": "1234567890",
                      "ifsc_code": "SBIN0001234",
                      "requested_at": "2026-06-15 12:00:00"
                  },
                  "created_at": "2026-06-15T12:00:00.000000Z",
                  "updated_at": "2026-06-15T12:00:00.000000Z"
              }
          ],
          "first_page_url": ".../api/v1/astrologer/wallet/withdrawals?page=1",
          "from": 1,
          "last_page": 1,
          "last_page_url": ".../api/v1/astrologer/wallet/withdrawals?page=1",
          "next_page_url": null,
          "path": ".../api/v1/astrologer/wallet/withdrawals",
          "per_page": 15,
          "prev_page_url": null,
          "to": 1,
          "total": 1
      }
  }
  ```

---

### 4. Create Withdrawal Request
Submits a request to withdraw funds from the wallet balance to a specified active bank account.

* **Method:** `POST`
* **URL:** `/wallet/withdraw`
* **Body Parameters (JSON):**
  - `amount` (Required, numeric): Amount to withdraw (must be at least 1).
  - `bank_account_id` (Required, integer): ID of the astrologer's bank account.
* **Response (Success - 201 Created):**
  ```json
  {
      "status": "success",
      "message": "Withdrawal request submitted successfully.",
      "data": {
          "transaction": {
              "wallet_id": 1,
              "transaction_type": "debit",
              "amount": 300,
              "status": "pending",
              "description": "Withdrawal Request",
              "meta": {
                  "bank_account_id": 1,
                  "account_holder_name": "Vikram Sharma",
                  "bank_name": "State Bank of India",
                  "account_number": "1234567890",
                  "ifsc_code": "SBIN0001234",
                  "requested_at": "2026-06-15 12:00:00"
              },
              "balance_before": "1000.00",
              "balance_after": "1000.00",
              "updated_at": "2026-06-15T12:00:00.000000Z",
              "created_at": "2026-06-15T12:00:00.000000Z",
              "id": 8
          },
          "available_balance": 700
      }
  }
  ```
* **Response (Validation Fail - 422 Unprocessable Entity):**
  - **Case 1: Invalid/Inactive Bank Account:**
    ```json
    {
        "status": "error",
        "message": "Invalid or inactive bank account selected."
    }
    ```
  - **Case 2: Insufficient Available Balance (considering pending withdrawals):**
    ```json
    {
        "status": "error",
        "message": "Insufficient available balance. Your total balance is ₹1000.00, but you have ₹300 in pending withdrawals."
    }
    ```
