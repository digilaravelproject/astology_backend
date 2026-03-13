<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AstrologerAuthController;
use App\Http\Controllers\Api\UserAuthController;

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
    });
});
