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

        // Wallet endpoints (requires auth)
        Route::middleware('auth:sanctum')->get('/wallet', [WalletController::class, 'show']);
        Route::middleware('auth:sanctum')->post('/wallet/topup', [WalletController::class, 'createTopup']);
        Route::middleware('auth:sanctum')->post('/wallet/topup/verify', [WalletController::class, 'verifyTopup']);
        Route::middleware('auth:sanctum')->get('/wallet/transactions', [WalletController::class, 'transactions']);
        Route::middleware('auth:sanctum')->get('/wallet/transactions/{id}', [WalletController::class, 'transactionDetail']);

        // Matrimony endpoints (requires auth)
        Route::middleware('auth:sanctum')->post('/matrimony/profile', [MatrimonyController::class, 'createProfile']);
        Route::middleware('auth:sanctum')->get('/matrimony/profiles', [MatrimonyController::class, 'listProfiles']);
        Route::middleware('auth:sanctum')->get('/matrimony/profiles/{id}', [MatrimonyController::class, 'showProfile']);
        Route::middleware('auth:sanctum')->get('/matrimony/search', [MatrimonyController::class, 'searchProfiles']);

    });
});
