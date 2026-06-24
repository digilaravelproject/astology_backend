# Astrologer Invoices API Documentation

This document describes the API endpoints for retrieving the monthly invoice summary and downloading raw invoice transaction logs for an astrologer.

---

## 1. Get Invoices Summary
Retrieves the astrologer's total earnings, total withdrawn, total invoices, and the list of invoices grouped by month.

- **URL**: `/api/v1/astrologer/wallet/invoices`
- **Method**: `GET`
- **Headers**:
  - `Authorization: Bearer <token>`
  - `Accept: application/json`

### Success Response (`200 OK`)
```json
{
  "status": "success",
  "data": {
    "total_earnings": 77503.76,
    "total_withdrawn": 20000,
    "total_invoices": 2,
    "status": "All Paid",
    "current_month": {
      "month_name": "June 2026",
      "gross_earnings": 0,
      "net_payable": 0,
      "total_withdrawn": 0,
      "status": "Paid"
    },
    "invoices": [
      {
        "month_name": "January 2026",
        "gross_earnings": 45403.76,
        "net_payable": 45403.76,
        "total_withdrawn": 20000,
        "status": "Paid",
        "download_url": "https://suryapathkundli.com/api/v1/astrologer/wallet/invoices/2026/01/download"
      },
      {
        "month_name": "December 2025",
        "gross_earnings": 32100,
        "net_payable": 32100,
        "total_withdrawn": 0,
        "status": "Paid",
        "download_url": "https://suryapathkundli.com/api/v1/astrologer/wallet/invoices/2025/12/download"
      }
    ]
  }
}
```

---

## 2. Download Monthly Invoice
Generates and downloads a beautifully styled PDF invoice representing the transaction breakdown for a specific month.

- **URL**: `/api/v1/astrologer/wallet/invoices/{year}/{month}/download`
- **Method**: `GET`
- **Headers**:
  - `Authorization: Bearer <token>`

### Success Response (`200 OK`)
- **Content-Type**: `application/pdf`
- **Content-Disposition**: `attachment; filename="invoice_2026_01.pdf"`
- **Response Body**: Binary PDF stream.

