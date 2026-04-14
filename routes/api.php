<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\Api\{
    AstrologerAuthController,
    AstrologerController,
    BlogController,
    CallController,
    ChatController,
    FeedbackController,
    FoundersWordController,
    MatrimonyController,
    NoticeController,
    NotificationController,
    PlanController,
    PresenceController,
    RemedyController,
    ReviewController,
    StaticPageController,
    TrainingVideoController,
    GiftController,
    UserAuthController,
    WalletController
};

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1
|
*/

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | ASTROLOGER ROUTES
    |--------------------------------------------------------------------------
    */
    Route::prefix('astrologer')->group(function () {
        Route::post('/signup', [AstrologerAuthController::class, 'signup']);
        Route::post('/send-otp', [AstrologerAuthController::class, 'sendOtp']);
        Route::post('/verify-otp', [AstrologerAuthController::class, 'verifyOtp']);
        Route::post('/resend-otp', [AstrologerAuthController::class, 'resendOtp']);
        Route::get('/profile/{userId}', [AstrologerAuthController::class, 'getProfile']);
        Route::get('/training-videos', [TrainingVideoController::class, 'index']);
        Route::get('/training-videos/{id}', [TrainingVideoController::class, 'show']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::put('/profile', [AstrologerAuthController::class, 'updateProfile']);
            Route::get('/home', [AstrologerAuthController::class, 'getHomeStatus']);
            Route::put('/home', [AstrologerAuthController::class, 'updateHomeStatus']);
            Route::put('/profile/skills', [AstrologerAuthController::class, 'updateSkill']);
            Route::put('/profile/other-details', [AstrologerAuthController::class, 'updateOtherDetails']);
            Route::post('/profile/photo', [AstrologerAuthController::class, 'updateProfilePhoto']);
            Route::put('/profile/photo', [AstrologerAuthController::class, 'updateProfilePhoto']);
            Route::get('/community/followers', [AstrologerAuthController::class, 'getFollowers']);
            Route::post('/community/followers/{userId}/toggle-like', [AstrologerAuthController::class, 'toggleFollowerLike']);
            Route::get('/community/favorites', [AstrologerAuthController::class, 'getFavorites']);
            Route::get('/phone-numbers', [AstrologerAuthController::class, 'getPhoneNumbers']);
            Route::post('/phone-numbers', [AstrologerAuthController::class, 'addPhoneNumber']);
            Route::post('/phone-numbers/{id}/verify', [AstrologerAuthController::class, 'verifyPhoneNumber']);
            Route::post('/phone-numbers/{id}/set-default', [AstrologerAuthController::class, 'setDefaultPhoneNumber']);
            Route::get('/bank-accounts', [AstrologerAuthController::class, 'getBankAccounts']);
            Route::post('/bank-accounts', [AstrologerAuthController::class, 'addBankAccount']);
            Route::post('/bank-accounts/{id}/set-default', [AstrologerAuthController::class, 'setDefaultBankAccount']);
            Route::get('/availability', [AstrologerAuthController::class, 'getAvailability']);
            Route::put('/availability', [AstrologerAuthController::class, 'setAvailability']);
            Route::delete('/availability/{day}/{slotIndex}', [AstrologerAuthController::class, 'deleteAvailability']);
            Route::get('/sleep-hours', [AstrologerAuthController::class, 'getSleepHours']);
            Route::post('/sleep-hours', [AstrologerAuthController::class, 'setSleepHours']);
            Route::post('/toggle-online', [AstrologerAuthController::class, 'toggleOnlineStatus']);
            Route::post('/logout', [AstrologerAuthController::class, 'logout']);
            Route::delete('/delete-account', [AstrologerAuthController::class, 'deleteAccount']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | USER (CONSUMER) ROUTES
    |--------------------------------------------------------------------------
    */
    Route::get('/gifts', [GiftController::class, 'index']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/gifts/send', [GiftController::class, 'send']);
        Route::get('/astrologers/{id}/gifts', [GiftController::class, 'astrologerGifts']);
    });

    Route::prefix('user')->group(function () {
        Route::post('/send-otp', [UserAuthController::class, 'sendOtp']);
        Route::post('/verify-otp', [UserAuthController::class, 'verifyOtp']);
        Route::post('/resend-otp', [UserAuthController::class, 'resendOtp']);
        Route::get('/profile/{userId}', [UserAuthController::class, 'getProfile']);
        Route::put('/profile/{userId}', [UserAuthController::class, 'updateProfile']); 

        Route::get('/founders-words', [FoundersWordController::class, 'index']);
        Route::get('/founders-words/{id}', [FoundersWordController::class, 'show']);
        Route::get('/remedies', [RemedyController::class, 'index']);
        Route::get('/remedies/{id}', [RemedyController::class, 'show']);
        Route::get('/blogs', [BlogController::class, 'index']);
        Route::get('/blogs/search', [BlogController::class, 'search']);
        Route::get('/blogs/{id}', [BlogController::class, 'show']);
        Route::get('/notices', [NoticeController::class, 'index']);
        Route::get('/astrologers', [AstrologerController::class, 'index']);
        Route::get('/astrologers/{id}', [AstrologerController::class, 'show']);
        Route::get('/reviews', [ReviewController::class, 'index']);
        Route::middleware('auth:sanctum')->get('/plans', [PlanController::class, 'index']);
        Route::get('/plans/{plan}', [PlanController::class, 'show']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::put('/profileInAppUpdate', [UserAuthController::class, 'updateInAppProfile']);
            Route::post('/profile/photo', [UserAuthController::class, 'updateProfilePhoto']);
            Route::post('/astrologers/{id}/follow', [UserAuthController::class, 'toggleFollowAstrologer']);
            Route::post('/astrologers/{id}/block', [UserAuthController::class, 'blockAstrologer']);
            Route::post('/astrologers/{id}/report', [UserAuthController::class, 'reportAstrologer']);
            Route::get('/following', [UserAuthController::class, 'getFollowing']);
            Route::get('/plan', [PlanController::class, 'current']);
            Route::post('/plans/upgrade', [PlanController::class, 'upgrade']);
            Route::post('/plans/upgrade/verify', [PlanController::class, 'verifyUpgrade']);
            Route::get('/wallet', [WalletController::class, 'show']);
            Route::post('/wallet/topup', [WalletController::class, 'createTopup']);
            Route::post('/wallet/topup/verify', [WalletController::class, 'verifyTopup']);
            Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
            Route::get('/wallet/transactions/{id}', [WalletController::class, 'transactionDetail']);
            Route::post('/reviews', [ReviewController::class, 'store']);
            Route::post('/reviews/{reviewId}/reply', [ReviewController::class, 'reply']);
            Route::get('/notifications/count', [NotificationController::class, 'count']);
            Route::get('/notifications', [NotificationController::class, 'list']);
            Route::get('/notifications/{id}', [NotificationController::class, 'show']);
            Route::put('/notifications/{id}/mark-read', [NotificationController::class, 'markRead']);
            Route::post('/matrimony/profile', [MatrimonyController::class, 'createProfile']);
            Route::put('/matrimony/profile', [MatrimonyController::class, 'updateProfile']);
            Route::get('/matrimony/profiles', [MatrimonyController::class, 'listProfiles']);
            Route::get('/matrimony/profiles_user_id/{user_id}', [MatrimonyController::class, 'showProfileOnUserId']);
            Route::get('/matrimony/profiles/{id}', [MatrimonyController::class, 'showProfile']);
            Route::get('/matrimony/search', [MatrimonyController::class, 'searchProfiles']);
            Route::post('/logout', [UserAuthController::class, 'logout']);
            Route::delete('/delete-account', [UserAuthController::class, 'deleteAccount']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | REAL-TIME SIGNALING & BROADCAST AUTH
    |--------------------------------------------------------------------------
    */
    // Public/Debug Broadcast Auth (Move inside v1 group)
    Route::post('/broadcasting/auth', function (\Illuminate\Http\Request $request) {
        \Illuminate\Support\Facades\Log::info("DEBUG Broadcast Auth Attempt", [
            'channel' => $request->channel_name,
            'user' => $request->user() ? $request->user()->id : 'GUEST'
        ]);
        return Broadcast::auth($request);
    })->middleware('auth:sanctum');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/presence/pulse', [PresenceController::class, 'pulse']);
        Route::post('/presence/offline', [PresenceController::class, 'offline']);

        Route::prefix('call')->group(function () {
            Route::post('/initiate', [CallController::class, 'initiateCall']);
            Route::post('/{sessionId}/accept', [CallController::class, 'acceptCall']);
            Route::post('/{sessionId}/reject', [CallController::class, 'rejectCall']);
            Route::post('/{sessionId}/end', [CallController::class, 'endCall']);
            Route::post('/{sessionId}/ice-candidate', [CallController::class, 'sendIceCandidate']);
        });

        Route::prefix('chat')->group(function () {
            Route::get('/sessions', [ChatController::class, 'getSessions']);
            Route::post('/initiate', [ChatController::class, 'initiateChat']);
            Route::post('/{sessionId}/accept', [ChatController::class, 'acceptChat']);
            Route::post('/{sessionId}/reject', [ChatController::class, 'rejectChat']);
            Route::post('/{sessionId}/end', [ChatController::class, 'endChat']);
            Route::post('/{sessionId}/message', [ChatController::class, 'sendMessage']);
            Route::post('/{sessionId}/read', [ChatController::class, 'markAsRead']);
            Route::post('/{sessionId}/sync-status', [ChatController::class, 'syncStatus']);
            Route::get('/{sessionId}/messages', [ChatController::class, 'getMessages']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | STATIC PAGES
    |--------------------------------------------------------------------------
    */
    Route::get('/static-pages', [StaticPageController::class, 'index']);
    Route::get('/static-pages/{type}', [StaticPageController::class, 'show']);
    Route::get('/faqs', [StaticPageController::class, 'getFaqs']);
    Route::get('/privacy-policy', [StaticPageController::class, 'getPrivacyPolicy']);
    Route::get('/terms-and-conditions', [StaticPageController::class, 'getTermsAndConditions']);
    Route::get('/payment-policy', [StaticPageController::class, 'getPaymentPolicy']);
    Route::get('/about-us', [StaticPageController::class, 'getAboutUs']);
    Route::get('/customer-support', [StaticPageController::class, 'getCustomerSupport']);

    /*
    |--------------------------------------------------------------------------
    | FEEDBACK
    |--------------------------------------------------------------------------
    */
    Route::post('/feedback', [FeedbackController::class, 'store'])->middleware('auth:sanctum');
    Route::get('/feedbacks', [FeedbackController::class, 'index']);
    Route::get('/feedbacks/{id}', [FeedbackController::class, 'show']);

});
