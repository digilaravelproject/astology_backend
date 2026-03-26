<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AstrologerAuthController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\AstrologerController;
use App\Http\Controllers\Api\FoundersWordController;
use App\Http\Controllers\Api\RemedyController;
use App\Http\Controllers\Api\NoticeController;
use App\Http\Controllers\Api\TrainingVideoController;
use App\Http\Controllers\Api\UserAuthController;
use App\Http\Controllers\Api\MatrimonyController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PlanController;

Route::prefix('v1')->group(function () {
    Route::prefix('astrologer')->group(function () {
        // Astrologer signup endpoint
        Route::post('/signup', [AstrologerAuthController::class, 'signup']);
        // OTP login routes
        Route::post('/send-otp', [AstrologerAuthController::class, 'sendOtp']);
        Route::post('/verify-otp', [AstrologerAuthController::class, 'verifyOtp']);
        Route::post('/resend-otp', [AstrologerAuthController::class, 'resendOtp']);
        
        // Get astrologer profile endpoint
        Route::get('/profile/{userId}', [AstrologerAuthController::class, 'getProfile']);

        // Update astrologer profile endpoint (requires authentication)
        Route::middleware('auth:sanctum')->put('/profile', [AstrologerAuthController::class, 'updateProfile']);

        // Home status (availability + pricing) endpoints (requires authentication)
        Route::middleware('auth:sanctum')->get('/home', [AstrologerAuthController::class, 'getHomeStatus']);
        Route::middleware('auth:sanctum')->put('/home', [AstrologerAuthController::class, 'updateHomeStatus']);

        // Update astrologer skill details (requires authentication)
        Route::middleware('auth:sanctum')->put('/profile/skills', [AstrologerAuthController::class, 'updateSkill']);

        // Update astrologer other details (requires authentication)
        Route::middleware('auth:sanctum')->put('/profile/other-details', [AstrologerAuthController::class, 'updateOtherDetails']);

        // Update profile photo endpoint (requires authentication)
        // Use POST + _method=PUT if you want to upload multipart/form-data from clients that don't support PUT file uploads.
        Route::middleware('auth:sanctum')->post('/profile/photo', [AstrologerAuthController::class, 'updateProfilePhoto']);
        Route::middleware('auth:sanctum')->put('/profile/photo', [AstrologerAuthController::class, 'updateProfilePhoto']);

        // Community (followers / favorites) endpoints
        Route::middleware('auth:sanctum')->get('/community/followers', [AstrologerAuthController::class, 'getFollowers']);
        Route::middleware('auth:sanctum')->post('/community/followers/{userId}/toggle-like', [AstrologerAuthController::class, 'toggleFollowerLike']);
        Route::middleware('auth:sanctum')->get('/community/favorites', [AstrologerAuthController::class, 'getFavorites']);

        // Astrologer phone numbers (requires authentication)
        Route::middleware('auth:sanctum')->post('/phone-numbers', [AstrologerAuthController::class, 'addPhoneNumber']);
        Route::middleware('auth:sanctum')->get('/phone-numbers', [AstrologerAuthController::class, 'getPhoneNumbers']);
        Route::middleware('auth:sanctum')->post('/phone-numbers/{id}/verify', [AstrologerAuthController::class, 'verifyPhoneNumber']);
        Route::middleware('auth:sanctum')->post('/phone-numbers/{id}/set-default', [AstrologerAuthController::class, 'setDefaultPhoneNumber']);

        // Astrologer bank accounts (requires authentication)
        Route::middleware('auth:sanctum')->get('/bank-accounts', [AstrologerAuthController::class, 'getBankAccounts']);
        Route::middleware('auth:sanctum')->post('/bank-accounts', [AstrologerAuthController::class, 'addBankAccount']);
        Route::middleware('auth:sanctum')->post('/bank-accounts/{id}/set-default', [AstrologerAuthController::class, 'setDefaultBankAccount']);

        // Astrologer availability (requires authentication)
        Route::middleware('auth:sanctum')->get('/availability', [AstrologerAuthController::class, 'getAvailability']);
        Route::middleware('auth:sanctum')->put('/availability', [AstrologerAuthController::class, 'setAvailability']);

        // Astrologer sleep hours (requires authentication)
        Route::middleware('auth:sanctum')->get('/sleep-hours', [AstrologerAuthController::class, 'getSleepHours']);
        Route::middleware('auth:sanctum')->post('/sleep-hours', [AstrologerAuthController::class, 'setSleepHours']);

        // Astrologer logout/delete account (requires authentication)
        Route::middleware('auth:sanctum')->post('/logout', [AstrologerAuthController::class, 'logout']);
        Route::middleware('auth:sanctum')->delete('/delete-account', [AstrologerAuthController::class, 'deleteAccount']);

        // Training videos (public)
        Route::get('/training-videos', [TrainingVideoController::class, 'index']);
        Route::get('/training-videos/{id}', [TrainingVideoController::class, 'show']);
    });

    Route::prefix('user')->group(function () {
        // User OTP login routes (creates account if doesn't exist)
        Route::post('/send-otp', [UserAuthController::class, 'sendOtp']);
        Route::post('/verify-otp', [UserAuthController::class, 'verifyOtp']);
        Route::post('/resend-otp', [UserAuthController::class, 'resendOtp']);
        
        // Get user profile endpoint
        Route::get('/profile/{userId}', [UserAuthController::class, 'getProfile']);
        
        // Update user profile endpoint (after OTP verification)
        Route::put('/profile/{userId}', [UserAuthController::class, 'updateProfile']);

        // Update authenticated user profile (requires auth)
        Route::middleware('auth:sanctum')->put('/profileInAppUpdate', [UserAuthController::class, 'updateInAppProfile']);
        Route::middleware('auth:sanctum')->post('/profile/photo', [UserAuthController::class, 'updateProfilePhoto']);

        // Founder words endpoints (public)
        Route::get('/founders-words', [FoundersWordController::class, 'index']);
        Route::get('/founders-words/{id}', [FoundersWordController::class, 'show']);

        // Remedies endpoints (public)
        Route::get('/remedies', [RemedyController::class, 'index']);
        Route::get('/remedies/{id}', [RemedyController::class, 'show']);

        // Blogs endpoints (public)
        Route::get('/blogs', [BlogController::class, 'index']);
        Route::get('/blogs/search', [BlogController::class, 'search']);
        Route::get('/blogs/{id}', [BlogController::class, 'show']);

        // Notices endpoints (public)
        Route::get('/notices', [NoticeController::class, 'index']);

        // Astrologers endpoints (public)
        Route::get('/astrologers', [AstrologerController::class, 'index']);
        Route::get('/astrologers/{id}', [AstrologerController::class, 'show']);

        // Follow / unfollow astrologer (requires auth)
        Route::middleware('auth:sanctum')->post('/astrologers/{id}/follow', [UserAuthController::class, 'toggleFollowAstrologer']);

        // Block astrologer (requires auth)
        Route::middleware('auth:sanctum')->post('/astrologers/{id}/block', [UserAuthController::class, 'blockAstrologer']);

        // Report astrologer (requires auth)
        Route::middleware('auth:sanctum')->post('/astrologers/{id}/report', [UserAuthController::class, 'reportAstrologer']);

        // User following, logout, delete account (requires auth)
        Route::middleware('auth:sanctum')->get('/following', [UserAuthController::class, 'getFollowing']);
        Route::middleware('auth:sanctum')->post('/logout', [UserAuthController::class, 'logout']);
        Route::middleware('auth:sanctum')->delete('/delete-account', [UserAuthController::class, 'deleteAccount']);

        // Plan endpoints (public + requires auth)
        Route::get('/plans', [PlanController::class, 'index']);
        Route::get('/plans/{plan}', [PlanController::class, 'show']);
        Route::middleware('auth:sanctum')->get('/plan', [PlanController::class, 'current']);
        Route::middleware('auth:sanctum')->post('/plans/upgrade', [PlanController::class, 'upgrade']);
        Route::middleware('auth:sanctum')->post('/plans/upgrade/verify', [PlanController::class, 'verifyUpgrade']);

        // Wallet endpoints (requires auth)
        Route::middleware('auth:sanctum')->get('/wallet', [WalletController::class, 'show']);
        Route::middleware('auth:sanctum')->post('/wallet/topup', [WalletController::class, 'createTopup']);

        // Reviews endpoints
        Route::middleware('auth:sanctum')->post('/reviews', [ReviewController::class, 'store']);
        Route::middleware('auth:sanctum')->post('/reviews/{reviewId}/reply', [ReviewController::class, 'reply']);
        Route::get('/reviews', [ReviewController::class, 'index']);

        // Notification endpoints
        Route::get('/notifications/count', [NotificationController::class, 'count']);
        Route::get('/notifications', [NotificationController::class, 'list']);
        Route::get('/notifications/{id}', [NotificationController::class, 'show']);
        Route::middleware('auth:sanctum')->put('/notifications/{id}/mark-read', [NotificationController::class, 'markRead']);

        Route::middleware('auth:sanctum')->post('/wallet/topup/verify', [WalletController::class, 'verifyTopup']);
        Route::middleware('auth:sanctum')->get('/wallet/transactions', [WalletController::class, 'transactions']);
        Route::middleware('auth:sanctum')->get('/wallet/transactions/{id}', [WalletController::class, 'transactionDetail']);

        // Matrimony endpoints (requires auth)
        Route::middleware('auth:sanctum')->post('/matrimony/profile', [MatrimonyController::class, 'createProfile']);
        Route::middleware('auth:sanctum')->get('/matrimony/profiles', [MatrimonyController::class, 'listProfiles']);
        Route::middleware('auth:sanctum')->get('/matrimony/profiles/{id}', [MatrimonyController::class, 'showProfile']);
        Route::middleware('auth:sanctum')->get('/matrimony/search', [MatrimonyController::class, 'searchProfiles']);

    });

    // Static Pages endpoints (public)
    Route::get('/static-pages', [\App\Http\Controllers\Api\StaticPageController::class, 'index']);
    Route::get('/static-pages/{type}', [\App\Http\Controllers\Api\StaticPageController::class, 'show']);
    Route::get('/faqs', [\App\Http\Controllers\Api\StaticPageController::class, 'getFaqs']);
    Route::get('/privacy-policy', [\App\Http\Controllers\Api\StaticPageController::class, 'getPrivacyPolicy']);
    Route::get('/terms-and-conditions', [\App\Http\Controllers\Api\StaticPageController::class, 'getTermsAndConditions']);
    Route::get('/payment-policy', [\App\Http\Controllers\Api\StaticPageController::class, 'getPaymentPolicy']);
});
