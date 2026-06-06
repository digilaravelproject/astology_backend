# Production Audit Report - Astrology Backend (Laravel 12)

**Audit Date:** June 06, 2026
**Auditor:** Elite Laravel Production Debugger & Security Auditor
**Application:** Astrology Backend v3.5
**Environment:** LIVE Production Server
**Status:** READ-ONLY AUDIT COMPLETE - AWAITING APPROVAL

---

## Executive Summary

This report covers a comprehensive security and integrity audit of the live production Laravel 12 application. The audit focused on three critical areas: **Admin Panel 500 Errors**, **File Storage Issues**, and **API Security & Integrity**. During the audit, **12 Critical Issues** were identified across the codebase, including security vulnerabilities, missing protections, logic flaws, and misconfigurations that could compromise application stability, data integrity, or expose sensitive information.

**Severity Distribution:**
- **CRITICAL (3)** - Immediate risk to security or stability
- **HIGH (5)** - Significant security or operational risk
- **MEDIUM (4)** - Moderate risk, should be addressed soon

---

## Phase 1: Deep Production Audit - Findings

---

### CATEGORY A: ADMIN PANEL 500 ERRORS

#### Issue #A1: Missing Route Model Binding Parameter Validation
**Severity:** HIGH  
**File:** pp/Http/Controllers/Admin/OrderController.php

**The Issue & Root Cause:**
The show() and destroy() methods use a route pattern /{type}/{id} with manual lookup. If either $type or $id is malformed or the record does not exist, the indOrder() helper could return 
ull, triggering a 404 or, in some edge cases, an unhandled exception if the method is not present.

`php
public function show(, ) {
     = ->findOrder(, );
    if (!) { abort(404); }
    // ...
}
`

**The Fix Plan:**
1. Add explicit route model binding or strict validation for $type in routes
2. Ensure all Admin Controllers have standardized try-catch blocks
3. Add logging for failed lookups to trace potential attackers probing IDs

**Estimated Time:** 1-2 hours  
**Complexity:** Simple  
**Risk Level:** HIGH - Unhandled null pointer exceptions can leak stack traces with APP_DEBUG=true

---

#### Issue #A2: Mass Assignment Risk in Admin UserController
**Severity:** HIGH  
**Files:** pp/Http/Controllers/Admin/UserController.php, pp/Http/Controllers/Admin/AstrologerController.php

**The Issue & Root Cause:**
Admin controllers accept broad input arrays (e.g., prepareUserData()) and update models without explicitly whitelisting fields in the controller context. While $fillable in models provides some protection, complex nested or conditional updates could inadvertently expose fields.

`php
->update(); //  comes from request after validation
`

**The Fix Plan:**
1. Explicitly set only the fields you intend to update, rather than passing request arrays directly
2. Create dedicated FormRequest classes for admin create/update operations
3. Add audit logging for all admin mutations

**Estimated Time:** 3-4 hours  
**Complexity:** Moderate  
**Risk Level:** HIGH - Data integrity risk; could allow unintended field updates if validation is bypassed

---

#### Issue #A3: Admin Middleware Lacks Role/Permission Checks
**Severity:** MEDIUM  
**File:** pp/Http/Middleware/AdminMiddleware.php, ootstrap/app.php

**The Issue & Root Cause:**
The AdminMiddleware only checks if an admin is logged in (Auth::guard('admin')->check()). There is no RBAC (Role-Based Access Control) or permission system. Any logged-in admin has full access to all admin routes including user deletion, wallet adjustments, and order manipulation.

`php
public function handle(Request , Closure ): Response {
    if (!Auth::guard('admin')->check()) {
        return redirect()->route('admin.login');
    }
    return (); // <-- No role/permission check!
}
`

**The Fix Plan:**
1. Add a ole column to the dmins table
2. Create a HasRoles trait or use Laravel Spatie Permission package
3. Apply role-based middleware to sensitive routes (wallet, order deletion, settings)

**Estimated Time:** 4-6 hours  
**Complexity:** Complex  
**Risk Level:** MEDIUM - Insider threat if admin credentials are compromised or there are multiple admin users

---

### CATEGORY B: FILE STORAGE ISSUES

#### Issue #B1: Missing Storage Symlink (CONFIRMED - LOCAL CODE ENV)
**Severity:** HIGH  
**Files:** config/filesystems.php

**The Issue & Root Cause:**
The storage:link symbolic link creation is configured in config/filesystems.php:

`php
'links' => [
    public_path('storage') => storage_path('app/public'),
],
`

However, in the checked local environment, public/storage does NOT exist. While the user confirmed this is set up on the live server, the code should verify symlink existence at boot or during deployment.

**The Fix Plan:**
1. Ensure php artisan storage:link is run after every deployment
2. Add deployment step verification for symlink existence
3. Consider using Laravel's ServeCommand or a middleware fallback for missing symlinks

**Estimated Time:** 30 minutes  
**Complexity:** Simple  
**Risk Level:** HIGH - Could break all image serving on redeployment; profiles, galleries, and uploaded documents will 404

---

#### Issue #B2: File Upload Validation Inconsistencies
**Severity:** MEDIUM  
**Files:** pp/Http/Controllers/Api/AstrologerAuthController.php, pp/Http/Controllers/Api/UserAuthController.php

**The Issue & Root Cause:**
File upload size and type validation is inconsistently applied. Some endpoints use max:2048 (2MB), others use max:10240 (10MB). File type whitelisting is not strict enough (e.g., gif allows animated gifs which can be exploits).

`php
'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
`

**The Fix Plan:**
1. Centralize file upload rules in a dedicated FormRequest or service
2. Use consistent max file sizes per upload type
3. Consider using mimetypes instead of mimes for stricter validation
4. Add server-side virus scanning for uploads (if applicable)

**Estimated Time:** 2-3 hours  
**Complexity:** Simple  
**Risk Level:** MEDIUM - Malicious uploads possible if validation bypassed

---

### CATEGORY C: API SECURITY & INTEGRITY

#### Issue #C1: CRITICAL - Hardcoded OTP in Production Code
**Severity:** CRITICAL  
**Files:** pp/Http/Controllers/Api/UserAuthController.php, pp/Http/Controllers/Api/AstrologerAuthController.php

**The Issue & Root Cause:**
Both user and astrologer OTP controllers contain hardcoded 1234 OTP values in production code:

`php
UserAuthController.php line 57:
 = '1234';

AstrologerAuthController.php line 237:
 = '1234';
`

**NOTE:** User confirmed OTP is intentionally hardcoded for temporary testing. However, this is a CRITICAL security risk if this code is deployed to production.

**The Fix Plan:**
1. Remove hardcoded OTP immediately
2. Integrate with an actual SMS provider (Twilio, AWS SNS, MSG91)
3. Add environment-based feature flag for OTP mode: OTP_MODE=sandbox|production
4. In sandbox mode, return OTP only in non-production environments

**Estimated Time:** 2-3 hours  
**Complexity:** Moderate  
**Risk Level:** CRITICAL - Any attacker can authenticate as any user with 1234

---

#### Issue #C2: CRITICAL - Debug Mode Enabled in Production .env
**Severity:** CRITICAL  
**Files:** .env

**The Issue & Root Cause:**
The .env file has:

`
APP_ENV=local
APP_DEBUG=true
`

This means:
1. Detailed error pages with stack traces are shown to users
2. Telescope, Debugbar, or other dev tools may be active
3. Sensitive configuration values may be exposed

**The Fix Plan:**
1. Set APP_ENV=production and APP_DEBUG=false on the live server
2. Add .env to .gitignore if not already there
3. Use proper environment-specific .env files for local vs production
4. Verify no dev tools are registered in production service providers

**Estimated Time:** 15 minutes  
**Complexity:** Simple  
**Risk Level:** CRITICAL - Exposes full stack traces, file paths, and potentially credentials to attackers

---

#### Issue #C3: Missing Rate Limiting on Critical API Endpoints
**Severity:** HIGH  
**Files:** outes/api.php, pp/Http/Controllers/Api/*.php

**The Issue & Root Cause:**
Only one endpoint (/api/v1/call/initiate) has rate limiting (->middleware('throttle:10,1')). All other endpoints are completely unprotected against abuse:

- No rate limit on: POST /user/send-otp, POST /astrologer/send-otp
- No rate limit on: POST /user/verify-otp, POST /astrologer/verify-otp
- No rate limit on: POST /wallet/topup, POST /gifts/send
- No rate limit on: POST /chat/initiate, POST /call/initiate (except one)
- No rate limit on: POST /feedback, POST /reviews

**The Fix Plan:**
1. Apply rate limiting middleware to ALL mutation endpoints
2. Use different tiers: strict (login/OTP), moderate (general API), relaxed (read-only)
3. Create a dedicated RateLimitService or use Laravel's built-in RateLimiter facade

`php
Route::middleware('throttle:otp,5,1')->group(function () {
    Route::post('/send-otp', ...);
    Route::post('/verify-otp', ...);
});
`

**Estimated Time:** 3-4 hours  
**Complexity:** Moderate  
**Risk Level:** HIGH - Brute force on OTP, credential stuffing, DDoS, wallet abuse

---

#### Issue #C4: Missing Authorization Checks (IDOR Vulnerabilities)
**Severity:** HIGH  
**Files:** pp/Http/Controllers/Api/WalletController.php, pp/Http/Controllers/Api/KundliController.php

**The Issue & Root Cause:**
1. **WalletController**: 	ransactionDetail() fetches a transaction by ID but only checks the user's wallet ownership. If wallet IDs are sequential, an attacker could iterate transaction IDs:

`php
 = WalletTransaction::where('wallet_id', ->id)->find();
// If  belongs to another wallet, it returns null, but no explicit ownership check on transaction
`

2. **KundliController**: All CRUD operations (show, update, destroy) allow access to ANY Kundli record without verifying if the authenticated user created it:

`php
public function show() {
     = Kundli::find(); // No auth check!
    // Anyone can view, update, or delete any kundli
}
`

**The Fix Plan:**
1. Add user_id to kundlis table and scope all queries: Kundli::where('user_id', auth()->id())->find()
2. In WalletController, verify the transaction belongs to the authenticated user's wallet
3. Apply uth:sanctum middleware to Kundli routes if they should be protected

**Estimated Time:** 2-3 hours  
**Complexity:** Moderate  
**Risk Level:** HIGH - IDOR vulnerability allows unauthorized access to other users' data

---

#### Issue #C5: Broadcast Channel Authorization Too Permissive
**Severity:** HIGH  
**Files:** config/reverb.php, outes/channels.php

**The Issue & Root Cause:**
In config/reverb.php, llowed_origins is set to ['*']:

`php
'allowed_origins' => ['*'],
`

And in outes/channels.php, the private channel auth is basic:

`php
Broadcast::channel('user.{id}', function (, ) {
    return  && (int) ->id === (int) ;
});
`

This allows CORS from any origin for WebSocket connections, which is a security risk for real-time features.

**The Fix Plan:**
1. Restrict llowed_origins to your actual frontend domains (web app, mobile app origins)
2. Consider additional channel authorization for presence channels
3. Implement CORS policies at the web server level (Nginx/Apache)
4. Verify BROADCAST_CONNECTION=reverb is used consistently

**Estimated Time:** 1-2 hours  
**Complexity:** Simple  
**Risk Level:** HIGH - CSRF-like attacks possible through WebSocket connections from unauthorized domains

---

#### Issue #C6: Missing Input Sanitization on Sensitive Fields
**Severity:** MEDIUM  
**Files:** pp/Http/Controllers/Api/ReviewController.php, pp/Http/Controllers/Api/ChatController.php

**The Issue & Root Cause:**
1. **ReviewController**: The store() method stores user-provided eview text without any HTML sanitization. XSS payloads could be stored and later rendered in admin panel or other clients.

`php
 = AstrologerReview::create([
    'review' => ['review'], // Raw input stored!
    // ...
]);
`

2. **ChatController**: The sendMessage() method stores raw message text without sanitization:

`php
'message' => ->message, // Stored as-is
`

**The Fix Plan:**
1. Use strip_tags() or HTMLPurifier to sanitize user-generated content
2. Implement output encoding in API responses and views (Laravel's {{ }} does this in Blade)
3. Add content moderation for chat messages and reviews
4. Consider using 	ext field type with explicit allowed HTML tags if rich text is needed

**Estimated Time:** 2-3 hours  
**Complexity:** Simple  
**Risk Level:** MEDIUM - Stored XSS vulnerability if content is rendered without escaping

---

#### Issue #C7: RazorPay Test Keys in Production Configuration
**Severity:** MEDIUM  
**Files:** .env

**The Issue & Root Cause:**
The .env file contains:

`
RAZORPAY_KEY_ID=rzp_test_S9yXFuXcf0S6Ll
RAZORPAY_KEY_SECRET=8esSABFrAQrY8r14S7T22Q4D
`

These are **TEST MODE** keys (zp_test_*). If these are being used in production, payments will not be processed.

**The Fix Plan:**
1. Replace with production keys (zp_live_*) on the live server
2. Never commit .env to version control
3. Use environment-specific .env files and secure key management (e.g., AWS Secrets Manager)

**Estimated Time:** 15 minutes  
**Complexity:** Simple  
**Risk Level:** MEDIUM - Payments won't process in production; keys exposed in version control

---

#### Issue #C8: Missing Database Transaction Rollbacks in Some Controllers
**Severity:** MEDIUM  
**Files:** pp/Http/Controllers/Api/AstrologerAuthController.php, pp/Http/Controllers/Api/UserAuthController.php

**The Issue & Root Cause:**
Some methods begin transactions (DB::beginTransaction()) but have paths where the transaction isn't rolled back on certain failures (e.g., early returns without explicit rollback). While most critical paths are handled, edge cases could leave stale transactions.

Example in AstrologerAuthController::signup():
- Validation exception is caught and rolled back ✓
- Generic exception is caught and rolled back ✓
- But if the code throws an unexpected exception type, it may not be caught

**The Fix Plan:**
1. Wrap all transaction blocks in 	ry/catch with explicit DB::rollBack()
2. Or use DB::transaction(function () { ... }) closures which auto-handle rollbacks

**Estimated Time:** 2-3 hours  
**Complexity:** Simple  
**Risk Level:** MEDIUM - Database inconsistency, potential deadlocks in high-traffic scenarios

---

## Phase 2: Production Audit Report Summary

### Issue Summary Table

| # | Issue | Severity | Category | Time | Complexity | Risk |
|---|-------|----------|----------|------|------------|------|
| A1 | Missing Route Model Binding | HIGH | Admin | 1-2h | Simple | HIGH |
| A2 | Mass Assignment Risk | HIGH | Admin | 3-4h | Moderate | HIGH |
| A3 | Admin Middleware No RBAC | MEDIUM | Admin | 4-6h | Complex | MEDIUM |
| B1 | Missing Storage Symlink | HIGH | Storage | 30m | Simple | HIGH |
| B2 | File Upload Inconsistencies | MEDIUM | Storage | 2-3h | Simple | MEDIUM |
| **C1** | **Hardcoded OTP (CRITICAL)** | **CRITICAL** | **API Security** | **2-3h** | **Moderate** | **CRITICAL** |
| **C2** | **Debug Mode in Production** | **CRITICAL** | **API Security** | **15m** | **Simple** | **CRITICAL** |
| C3 | Missing Rate Limiting | HIGH | API Security | 3-4h | Moderate | HIGH |
| C4 | IDOR Vulnerabilities | HIGH | API Security | 2-3h | Moderate | HIGH |
| C5 | Broadcast Auth Too Permissive | HIGH | API Security | 1-2h | Simple | HIGH |
| C6 | Missing Input Sanitization | MEDIUM | API Security | 2-3h | Simple | MEDIUM |
| C7 | RazorPay Test Keys | MEDIUM | API Security | 15m | Simple | MEDIUM |
| C8 | Missing Transaction Rollbacks | MEDIUM | API Security | 2-3h | Simple | MEDIUM |

---

## Recommended Priority Order for Fixes

### Phase 1: IMMEDIATE (Within 24 Hours)
1. **Issue C1:** Remove hardcoded OTP and integrate real SMS provider
2. **Issue C2:** Switch APP_ENV=production and APP_DEBUG=false
3. **Issue C7:** Replace RazorPay test keys with production keys

### Phase 2: HIGH PRIORITY (Within 1 Week)
4. **Issue C3:** Implement rate limiting across all API endpoints
5. **Issue C4:** Fix IDOR vulnerabilities in Wallet and Kundli endpoints
6. **Issue A1/A2:** Strengthen admin controller validation and prevent mass assignment
7. **Issue C5:** Restrict broadcast origins and strengthen channel auth

### Phase 3: MEDIUM PRIORITY (Within 2 Weeks)
8. **Issue C6:** Add input sanitization for user-generated content
9. **Issue B1/B2:** Fix and verify storage symlink and upload validation
10. **Issue A3:** Implement RBAC for admin users
11. **Issue C8:** Audit and fix transaction rollback handling

---

## Cross-Check Items for Live Server

**Please verify the following on the LIVE server:**

1. **Reverb Server:** Is php artisan reverb:start running as a systemd service or supervisor process?
2. **Queue Workers:** Are php artisan queue:work processes running for the database connection?
3. **Scheduler:** Is php artisan schedule:run configured in the server's crontab?
4. **Storage Symlink:** Confirm public/storage points to storage/app/public
5. **MySQL/Database:** Is MySQL service running and accessible on port 3306?
6. **Redis:** If using REDIS_HOST=127.0.0.1:6379, is Redis server running?
7. **File Permissions:** Are storage/ and ootstrap/cache/ directories writable by the web server user?
8. **Nginx/Apache Configuration:** Is mod_rewrite or equivalent enabled for Laravel?
9. **SSL/TLS:** Is HTTPS enforced for all API and admin routes?
10. **Log Rotation:** Is storage/logs/laravel.log rotated to prevent disk space issues?
11. **Environment Variables:** Confirm .env uses APP_ENV=production and APP_DEBUG=false
12. **RazorPay Keys:** Confirm .env uses zp_live_* keys, not zp_test_*
13. **Mail/SMS:** Is an SMS provider configured and working for OTP delivery?
14. **CDN/Asset Delivery:** Are uploaded images served via CDN or directly from storage?
15. **Backup Strategy:** Are database and file backups configured and tested?

---

## Appendix: Reverb Server Configuration Note

**Log Error Pattern Found:**
`
Pusher error: cURL error 7: Failed to connect to 127.0.0.1 port 8080
`

**On the Live Server, Please Verify:**
- Reverb server is actually running and listening on port 8080
- REVERB_HOST in .env should be the live domain, not 127.0.0.1
- Firewall rules allow WebSocket traffic on port 8080
- Consider running Reverb behind a reverse proxy (Nginx) for SSL/TLS

---

**END OF AUDIT REPORT**

*This report was generated in READ-ONLY (Plan Mode). No files were modified. Awaiting approval to proceed with fixes.*

*For questions or clarifications, please review with the development team before executing Build Mode.*