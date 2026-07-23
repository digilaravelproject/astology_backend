# Admin Settings & Operators API/Web Endpoints Documentation

This document outlines the routes, controller functions, and settings structure implemented to support dynamic configurations and admin/operator user accounts (RBAC).

## Endpoints

### 1. View Settings
- **Route**: `GET /admin/settings`
- **Name**: `admin.settings.index`
- **Controller Action**: `SettingController@index`
- **Description**: Loads all system settings from the database and compiles them for rendering in the dashboard. Also retrieves all registered admin operators.

### 2. Update Settings
- **Route**: `POST /admin/settings`
- **Name**: `admin.settings.update`
- **Controller Action**: `SettingController@update`
- **Description**: Updates application-wide settings.
- **Payload Parameters**:
  - `app_name` (string)
  - `support_email` (email)
  - `seo_meta_description` (string)
  - `favicon` (file/image)
  - `logo` (file/image)
  - `social_preview` (file/image)
  - `global_commission_percentage` (numeric)
  - `ecommerce_commission_percentage` (numeric)
  - `premium_yearly_commission_percentage` (numeric)
  - `min_wallet_recharge` (numeric)
  - `max_wallet_balance` (numeric)
  - `min_withdrawal_amount` (numeric)
  - `razorpay_key` (string)
  - `razorpay_secret` (string)
  - `stripe_key` (string)
  - `stripe_secret` (string)
  - `payment_gateway_mode` (in: sandbox, live)
  - `active_gateways` (array)
  - `default_chat_rate_per_minute` (numeric)
  - `default_call_rate_per_minute` (numeric)
  - `default_video_call_rate_per_minute` (numeric)
  - `default_po_at_5_rate_per_minute` (numeric)
  - `rate_limit_enabled` (boolean)
  - `rate_limit_*` (integer)

### 3. Add Team Operator
- **Route**: `POST /admin/settings/operators`
- **Name**: `admin.settings.operators.store`
- **Controller Action**: `SettingController@storeOperator`
- **Description**: Registers a new administrator.
- **Payload Parameters**:
  - `name` (required, string)
  - `email` (required, email, unique:admins)
  - `password` (required, string, min:6)
  - `phone` (nullable, string)
  - `role` (required, in: super_admin, admin)
  - `is_active` (required, boolean)

### 4. Edit Team Operator
- **Route**: `PUT /admin/settings/operators/{id}`
- **Name**: `admin.settings.operators.update`
- **Controller Action**: `SettingController@updateOperator`
- **Description**: Modifies settings for an existing administrator.
- **Payload Parameters**: Same as create, with `password` being optional.

### 5. Remove Team Operator
- **Route**: `DELETE /admin/settings/operators/{id}`
- **Name**: `admin.settings.operators.destroy`
- **Controller Action**: `SettingController@destroyOperator`
- **Description**: Deletes an operator account. Prevents self-deletion or deleting the last active Super Admin.
