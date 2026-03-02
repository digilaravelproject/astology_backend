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
    });
});
