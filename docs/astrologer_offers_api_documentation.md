# Astrologer Offers & Commission Management API Documentation

This document describes the API endpoints, schemas, headers, request structures, and response objects for the Astrologer Offers & Commission Management System. These endpoints are designed for integration by the frontend (Flutter) client.

---

## 1. Authentication Headers
All endpoints require authentication. Include the astrologer's bearer token in the HTTP headers:
```http
Authorization: Bearer <your_jwt_or_sanctum_token>
Accept: application/json
Content-Type: application/json
```

---

## 2. API Endpoints Reference

### 2.1. List Available Offers
Retrieve the list of active promotions. The API automatically calculates the astrologer's dynamic discounted rate and payout splits for both chat and voice calls.

- **URL:** `/api/v1/astrologer/offers`
- **Method:** `GET`
- **Authentication Required:** Yes (Role: `astrologer`)

#### Response (`200 OK`)
```json
{
  "status": "success",
  "message": "Available offers retrieved successfully.",
  "data": {
    "offers": [
      {
        "id": 1,
        "name": "Super Summer Discount",
        "description": "Get 20% discount on chat/call services with 80% astrologer payout split",
        "discount_percentage": 20.00,
        "expires_at": "2026-12-31T23:59:59.000000Z",
        "is_currently_active_for_me": false,
        "calculated_pricing": {
          "chat": {
            "base_rate_per_minute": 15.00,
            "discounted_rate_per_minute": 12.00,
            "astrologer_share_percentage": 80.00,
            "admin_share_percentage": 20.00,
            "estimated_astrologer_earning_per_minute": 9.60,
            "estimated_admin_earning_per_minute": 2.40
          },
          "call": {
            "base_rate_per_minute": 20.00,
            "discounted_rate_per_minute": 16.00,
            "astrologer_share_percentage": 80.00,
            "admin_share_percentage": 20.00,
            "estimated_astrologer_earning_per_minute": 12.80,
            "estimated_admin_earning_per_minute": 3.20
          }
        }
      }
    ]
  }
}
```

---

### 2.2. Toggle Offer Activation
Activate or deactivate a specific offer. **Rule: Only one offer can be active at a time for an astrologer.** Activating an offer automatically deactivates any previously active offer.

- **URL:** `/api/v1/astrologer/offers/{id}/activate`
- **Method:** `POST`
- **URL Parameters:**
  - `id` (integer, Required) - The ID of the offer to activate/deactivate.
- **Authentication Required:** Yes (Role: `astrologer`)

#### Response - Case A: Activating a New Offer (`200 OK`)
```json
{
  "status": "success",
  "message": "Offer 'Super Summer Discount' has been successfully activated.",
  "data": {
    "offer_id": 1,
    "status": "active"
  }
}
```

#### Response - Case B: Deactivating the Currently Active Offer (`200 OK`)
```json
{
  "status": "success",
  "message": "Offer has been successfully deactivated.",
  "data": {
    "offer_id": 1,
    "status": "inactive"
  }
}
```

#### Response - Error: Offer Expired or Not Found (`400 Bad Request` / `404 Not Found`)
```json
{
  "status": "error",
  "message": "Offer has expired and cannot be activated.",
  "code": 400
}
```

---

### 2.3. Offer Activation History
Retrieve the history of all offers associated with the authenticated astrologer, including current status and timestamp of activation.

- **URL:** `/api/v1/astrologer/offers/history`
- **Method:** `GET`
- **Authentication Required:** Yes (Role: `astrologer`)

#### Response (`200 OK`)
```json
{
  "status": "success",
  "message": "Offer activation history retrieved.",
  "data": {
    "history": [
      {
        "offer_id": 1,
        "offer_name": "Super Summer Discount",
        "discount_percentage": 20.00,
        "status": "active",
        "activated_at": "2026-06-24T12:00:00.000000Z",
        "deactivated_at": null
      },
      {
        "offer_id": 2,
        "offer_name": "Winter Splash",
        "discount_percentage": 10.00,
        "status": "expired",
        "activated_at": "2026-01-10T08:00:00.000000Z",
        "deactivated_at": "2026-01-20T18:00:00.000000Z"
      }
    ]
  }
}
```

---

## 3. Dynamic Calculation Formula Guide

For UI renderings:
- **Strikethrough Price (Base Rate):** Display `base_rate_per_minute`.
- **New Price (Discounted Rate):** Display `discounted_rate_per_minute`.
- **Admin Split / Payout Share:** Tell the astrologer exactly how much they earn per minute using `estimated_astrologer_earning_per_minute`.

*Formula applied on the backend:*
$$\text{Discounted Price} = \text{Base Price} \times \left(1 - \frac{\text{Discount \%}}{100}\right)$$
$$\text{Astrologer Share} = \text{Discounted Price} \times \frac{\text{Astrologer Split \%}}{100}$$
$$\text{Admin Commission} = \text{Discounted Price} \times \frac{\text{Admin Split \%}}{100}$$
