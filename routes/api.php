<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AstrologerAuthController,
    AstrologerController,
    AstrologerDefaultMessageController,
    AstrologerGalleryController,
    AstrologerOfferController,
    AstrologerPriceIncreaseController,
    AstrologerWalletController,
    BlogController,
    CallController,
    ChatAssistanceController,
    ChatController,
    DeviceTokenController,
    FeedbackController,
    FoundersWordController,
    GiftController,
    KundliController,
    LiveSessionController,
    MatrimonyController,
    NoticeController,
    NotificationController,
    PackageSessionController,
    PlanController,
    PresenceController,
    RemedyController,
    ReviewController,
    StaticPageController,
    SuperChatController,
    TrainingVideoController,
    TurnCredentialsController,
    UserAuthController,
    WalletController
};

/*
|--------------------------------------------------------------------------
| REST API Routes - Version 1 (/api/v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | 1. ASTROLOGER (PROVIDER) DOMAIN
    |--------------------------------------------------------------------------
    */
    Route::prefix('astrologer')->group(function () {

        // ── Public Auth & Registration ───────────────────────────────────
        Route::post('/signup',      [AstrologerAuthController::class, 'signup'])->middleware('throttle:auth');
        Route::post('/send-otp',    [AstrologerAuthController::class, 'sendOtp'])->middleware('throttle:otp');
        Route::post('/verify-otp',  [AstrologerAuthController::class, 'verifyOtp'])->middleware('throttle:otp');
        Route::post('/resend-otp',  [AstrologerAuthController::class, 'resendOtp'])->middleware('throttle:otp');
        Route::get('/profile/{userId}', [AstrologerAuthController::class, 'getProfile'])->middleware('throttle:general');

        // ── Public Training Videos ───────────────────────────────────────
        Route::get('/training-videos',      [TrainingVideoController::class, 'index'])->middleware('throttle:general');
        Route::get('/training-videos/{id}',  [TrainingVideoController::class, 'show'])->middleware('throttle:general');

        // ── Authenticated Astrologer Endpoints ────────────────────────────
        Route::middleware(['auth:sanctum', 'astrologer', 'throttle:tiered'])->group(function () {

            // Performance & Orders
            Route::get('/orders',       [AstrologerController::class, 'getOrders']);
            Route::get('/performance',  [AstrologerController::class, 'getPerformance']);

            // Profile & Settings
            Route::match(['put', 'post'], '/profile',               [AstrologerAuthController::class, 'updateProfile']);
            Route::get('/home',                 [AstrologerAuthController::class, 'getHomeStatus']);
            Route::put('/home',                 [AstrologerAuthController::class, 'updateHomeStatus']);
            Route::get('/home-settings',        [AstrologerAuthController::class, 'getHomeSettings']);
            Route::put('/home-settings',        [AstrologerAuthController::class, 'updateHomeSettings']);
            Route::match(['put', 'post'], '/profile/skills',        [AstrologerAuthController::class, 'updateSkill']);
            Route::match(['put', 'post'], '/profile/other-details', [AstrologerAuthController::class, 'updateOtherDetails']);
            Route::match(['put', 'post'], '/profile/photo',         [AstrologerAuthController::class, 'updateProfilePhoto']);
            Route::post('/toggle-online',       [AstrologerAuthController::class, 'toggleOnlineStatus']);

            // Availability & Sleep Hours
            Route::get('/availability',                     [AstrologerAuthController::class, 'getAvailability']);
            Route::put('/availability',                     [AstrologerAuthController::class, 'setAvailability']);
            Route::delete('/availability/{day}/{slotIndex}',[AstrologerAuthController::class, 'deleteAvailability']);
            Route::get('/sleep-hours',                      [AstrologerAuthController::class, 'getSleepHours']);
            Route::post('/sleep-hours',                     [AstrologerAuthController::class, 'setSleepHours']);

            // Community & Followers & Blocks
            Route::get('/community/followers',                     [AstrologerAuthController::class, 'getFollowers']);
            Route::post('/community/followers/{userId}/toggle-like', [AstrologerAuthController::class, 'toggleFollowerLike']);
            Route::get('/community/favorites',                     [AstrologerAuthController::class, 'getFavorites']);
            Route::post('/users/{id}/block',                        [AstrologerAuthController::class, 'blockUser']);
            Route::post('/users/{id}/unblock',                      [AstrologerAuthController::class, 'unblockUser']);
            Route::get('/blocked-users',                            [AstrologerAuthController::class, 'getBlockedUsers']);

            // Verification: Phone Numbers
            Route::get('/phone-numbers',                    [AstrologerAuthController::class, 'getPhoneNumbers']);
            Route::post('/phone-numbers',                   [AstrologerAuthController::class, 'addPhoneNumber']);
            Route::post('/phone-numbers/{id}/verify',       [AstrologerAuthController::class, 'verifyPhoneNumber']);
            Route::post('/phone-numbers/{id}/set-default',  [AstrologerAuthController::class, 'setDefaultPhoneNumber']);

            // Verification: Bank Accounts & Billing
            Route::get('/bank-accounts',                    [AstrologerAuthController::class, 'getBankAccounts']);
            Route::post('/bank-accounts',                   [AstrologerAuthController::class, 'addBankAccount']);
            Route::post('/bank-accounts/{id}/set-default',  [AstrologerAuthController::class, 'setDefaultBankAccount']);
            Route::get('/billing-address',                  [AstrologerAuthController::class, 'getBillingAddress']);
            Route::put('/billing-address',                  [AstrologerAuthController::class, 'updateBillingAddress']);

            // Default Automated Chat Greeting Messages
            Route::prefix('default-messages')->group(function () {
                Route::get('/',                 [AstrologerDefaultMessageController::class, 'index']);
                Route::get('/active',           [AstrologerDefaultMessageController::class, 'getActive']);
                Route::post('/',                [AstrologerDefaultMessageController::class, 'store']);
                Route::put('/{id}',             [AstrologerDefaultMessageController::class, 'update']);
                Route::delete('/{id}',          [AstrologerDefaultMessageController::class, 'destroy']);
                Route::post('/{id}/set-default',[AstrologerDefaultMessageController::class, 'setDefault']);
            });

            // Wallet & Financial Earnings
            Route::prefix('wallet')->group(function () {
                Route::get('/',                             [AstrologerWalletController::class, 'show']);
                Route::get('/withdrawal-config',            [AstrologerWalletController::class, 'withdrawalConfig']);
                Route::get('/earnings',                     [AstrologerWalletController::class, 'earnings']);
                Route::get('/withdrawals',                  [AstrologerWalletController::class, 'withdrawals']);
                Route::get('/withdrawals/{id}/receipt',     [AstrologerWalletController::class, 'downloadWithdrawalReceipt']);
                Route::post('/withdraw',                    [AstrologerWalletController::class, 'withdraw']);
                Route::get('/weekly-rankings',              [AstrologerWalletController::class, 'weeklyRankings']);
                Route::get('/invoices',                     [AstrologerWalletController::class, 'invoices']);
                Route::get('/invoices/{year}/{month}/download', [AstrologerWalletController::class, 'downloadInvoice']);
            });

            // Gallery Photos Management
            Route::prefix('gallery')->group(function () {
                Route::get('/',                     [AstrologerGalleryController::class, 'index']);
                Route::post('/upload',              [AstrologerGalleryController::class, 'storeMultiple']);
                Route::get('/count',                [AstrologerGalleryController::class, 'getTotalImages']);
                Route::put('/{id}/toggle-visibility', [AstrologerGalleryController::class, 'toggleVisibility']);
                Route::delete('/{id}',              [AstrologerGalleryController::class, 'destroy']);
            });

            // Live Stream Broadcasting (Astrologer Side)
            Route::prefix('live')->group(function () {
                Route::get('/',             [LiveSessionController::class, 'index']);
                Route::post('/',            [LiveSessionController::class, 'store']);
                Route::get('/current',      [LiveSessionController::class, 'current']);
                Route::get('/{id}',         [LiveSessionController::class, 'show']);
                Route::put('/{id}',         [LiveSessionController::class, 'update']);
                Route::delete('/{id}',      [LiveSessionController::class, 'destroy']);
                Route::post('/{id}/start',  [LiveSessionController::class, 'start']);
                Route::post('/{id}/stop',   [LiveSessionController::class, 'stop']);
                Route::post('/{id}/broadcast',      [LiveSessionController::class, 'broadcast']);
                Route::post('/{id}/stop-broadcast', [LiveSessionController::class, 'stopBroadcast']);
                Route::post('/{id}/media-status',   [LiveSessionController::class, 'updateMediaStatus']);
                Route::get('/{id}/comments',        [SuperChatController::class, 'comments']);
            });

            // Price Increase Requests
            Route::prefix('price-increase')->group(function () {
                Route::get('/status',   [AstrologerPriceIncreaseController::class, 'status']);
                Route::post('/request', [AstrologerPriceIncreaseController::class, 'requestIncrease']);
                Route::get('/history',  [AstrologerPriceIncreaseController::class, 'history']);
            });

            // Offers & Commission Discounts
            Route::prefix('offers')->group(function () {
                Route::get('/',             [AstrologerOfferController::class, 'index']);
                Route::post('/{id}/activate',[AstrologerOfferController::class, 'activate']);
                Route::get('/history',      [AstrologerOfferController::class, 'history']);
            });

            // Push Notification Device Token
            Route::post('/device-token', [DeviceTokenController::class, 'store']);
            Route::post('/remove-token', [DeviceTokenController::class, 'remove']);

            // Session & Account Lifecycle
            Route::post('/logout',          [AstrologerAuthController::class, 'logout']);
            Route::delete('/delete-account',[AstrologerAuthController::class, 'deleteAccount']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 2. GIFTS & VIRTUAL TIPPING
    |--------------------------------------------------------------------------
    */
    Route::get('/gifts', [GiftController::class, 'index'])->middleware('throttle:general');
    Route::middleware(['auth:sanctum', 'throttle:tiered'])->group(function () {
        Route::post('/gifts/send',              [GiftController::class, 'send']);
        Route::get('/astrologers/{id}/gifts',   [GiftController::class, 'astrologerGifts']);
    });

    /*
    |--------------------------------------------------------------------------
    | 3. USER (CONSUMER) DOMAIN
    |--------------------------------------------------------------------------
    */
    Route::prefix('user')->group(function () {

        // ── Public Auth & User Profiles ───────────────────────────────────
        Route::post('/send-otp',        [UserAuthController::class, 'sendOtp'])->middleware('throttle:otp');
        Route::post('/verify-otp',      [UserAuthController::class, 'verifyOtp'])->middleware('throttle:otp');
        Route::post('/resend-otp',      [UserAuthController::class, 'resendOtp'])->middleware('throttle:otp');
        Route::get('/profile/{userId}', [UserAuthController::class, 'getProfile'])->whereNumber('userId')->middleware('throttle:general');
        Route::match(['put', 'post'], '/profile/{userId}', [UserAuthController::class, 'updateProfile'])->whereNumber('userId')->middleware('throttle:general');

        // ── Public Content Discovery ─────────────────────────────────────
        Route::get('/founders-words',       [FoundersWordController::class, 'index'])->middleware('throttle:general');
        Route::get('/founders-words/{id}',  [FoundersWordController::class, 'show'])->middleware('throttle:general');
        Route::get('/remedies',             [RemedyController::class, 'index'])->middleware('throttle:general');
        Route::get('/remedies/{id}',        [RemedyController::class, 'show'])->middleware('throttle:general');
        Route::get('/blogs',                [BlogController::class, 'index'])->middleware('throttle:general');
        Route::get('/blogs/search',         [BlogController::class, 'search'])->middleware('throttle:general');
        Route::get('/blogs/{id}',           [BlogController::class, 'show'])->middleware('throttle:general');
        Route::get('/notices',              [NoticeController::class, 'index'])->middleware('throttle:general');
        Route::get('/astrologers',          [AstrologerController::class, 'index'])->middleware('throttle:general');
        Route::get('/astrologers/{id}',     [AstrologerController::class, 'show'])->middleware('throttle:general');
        Route::get('/astrologers/{id}/gallery', [AstrologerController::class, 'getGallery'])->middleware('throttle:general');
        Route::get('/reviews',              [ReviewController::class, 'index'])->middleware('throttle:general');
        Route::get('/plans/{plan}',         [PlanController::class, 'show'])->middleware('throttle:general');

        // ── Authenticated User Features ───────────────────────────────────
        Route::middleware(['auth:sanctum', 'user', 'throttle:tiered'])->group(function () {

            // Profile & Social Follows
            Route::match(['put', 'post'], '/profileInAppUpdate', [UserAuthController::class, 'updateInAppProfile']);
            Route::match(['put', 'post'], '/profile/photo',      [UserAuthController::class, 'updateProfilePhoto']);
            Route::post('/astrologers/{id}/follow',         [UserAuthController::class, 'toggleFollowAstrologer']);
            Route::post('/astrologers/{id}/block',          [UserAuthController::class, 'blockAstrologer']);
            Route::post('/astrologers/{id}/unblock',        [UserAuthController::class, 'unblockAstrologer']);
            Route::post('/astrologers/{id}/report',         [UserAuthController::class, 'reportAstrologer']);
            Route::get('/following',                        [UserAuthController::class, 'getFollowing']);
            Route::get('/blocked-astrologers',              [UserAuthController::class, 'getBlockedAstrologers']);

            // Membership Plans
            Route::get('/plans',                [PlanController::class, 'index']);
            Route::get('/plan',                 [PlanController::class, 'current']);
            Route::post('/plans/upgrade',        [PlanController::class, 'upgrade']);
            Route::post('/plans/upgrade/verify', [PlanController::class, 'verifyUpgrade']);

            // Consumer Wallet
            Route::get('/wallet',                       [WalletController::class, 'show']);
            Route::post('/wallet/topup',                [WalletController::class, 'createTopup']);
            Route::post('/wallet/topup/verify',         [WalletController::class, 'verifyTopup']);
            Route::get('/wallet/transactions',          [WalletController::class, 'transactions']);
            Route::get('/wallet/transactions/{id}',     [WalletController::class, 'transactionDetail']);
            Route::get('/wallet/transactions/{id}/invoice', [WalletController::class, 'downloadInvoice']);

            // Reviews & Ratings
            Route::post('/reviews',                     [ReviewController::class, 'store']);
            Route::post('/reviews/{reviewId}/reply',    [ReviewController::class, 'reply']);

            // Live Stream Interactions (Consumer Side)
            Route::get('/live/now',             [SuperChatController::class, 'nowStreaming']);
            Route::get('/live/{id}',            [SuperChatController::class, 'show']);
            Route::post('/live/{id}/join',      [SuperChatController::class, 'join']);
            Route::post('/live/{id}/watch',     [SuperChatController::class, 'watch'])
                ->withoutMiddleware('throttle:tiered')
                ->middleware('throttle:live_watch');
            Route::post('/live/{id}/leave',     [SuperChatController::class, 'leave']);
            Route::post('/live/{id}/comment',   [SuperChatController::class, 'comment'])->middleware('throttle:tiered');
            Route::post('/live/{id}/super-chat',[SuperChatController::class, 'sendSuperChat']);
            Route::get('/live/{id}/comments',   [SuperChatController::class, 'comments']);

            // Device Token Management (FCM)
            Route::post('/device-token',        [DeviceTokenController::class, 'store']);
            Route::post('/remove-token',        [DeviceTokenController::class, 'remove']);

            // In-App Notification Center
            Route::get('/notifications/count',              [NotificationController::class, 'count']);
            Route::get('/notifications',                    [NotificationController::class, 'list']);
            Route::get('/notifications/{id}',               [NotificationController::class, 'show']);
            Route::put('/notifications/{id}/mark-read',      [NotificationController::class, 'markRead']);
            Route::post('/notifications/{id}/mark-read',     [NotificationController::class, 'markRead']);
            Route::post('/notifications/mark-all-read',      [NotificationController::class, 'markAllRead']);
            Route::delete('/notifications/{id}',            [NotificationController::class, 'destroy']);

            // Matrimony Profiles
            Route::post('/matrimony/profile',                       [MatrimonyController::class, 'createProfile']);
            Route::post('/matrimony/update_profile',                [MatrimonyController::class, 'updateProfile']);
            Route::get('/matrimony/profiles',                       [MatrimonyController::class, 'listProfiles']);
            Route::get('/matrimony/profiles_user_id/{user_id}',     [MatrimonyController::class, 'showProfileOnUserId']);
            Route::get('/matrimony/profiles/{id}',                  [MatrimonyController::class, 'showProfile']);
            Route::get('/matrimony/search',                         [MatrimonyController::class, 'searchProfiles']);

            // Prepaid Packages & Sub-Sessions
            Route::prefix('packages')->group(function () {
                Route::post('/purchase',        [PackageSessionController::class, 'purchase']);
                Route::get('/active-status',    [PackageSessionController::class, 'activeStatus']);
                Route::post('/session/start',   [PackageSessionController::class, 'startSession']);
                Route::post('/session/end',     [PackageSessionController::class, 'endSession']);
            });

            // Account Lifecycle
            Route::post('/logout',          [UserAuthController::class, 'logout']);
            Route::delete('/delete-account',[UserAuthController::class, 'deleteAccount']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 4. REAL-TIME SIGNALING & BROADCAST AUTH
    |--------------------------------------------------------------------------
    */
    Route::post('/broadcasting/auth', function (Request $request) {
        return Broadcast::auth($request);
    })->middleware(['auth:sanctum', 'throttle:general']);

    Route::middleware(['auth:sanctum', 'throttle:tiered'])->group(function () {

        // Real-Time Online Presence
        Route::post('/presence/pulse',      [PresenceController::class, 'pulse']);
        Route::post('/presence/offline',    [PresenceController::class, 'offline']);

        // WebRTC Audio/Video Call Signaling & Lifecycle
        Route::prefix('call')->group(function () {
            Route::get('/sessions/user',                [CallController::class, 'getUserSessions']);
            Route::get('/sessions/astrologer',          [CallController::class, 'getAstrologerSessions']);
            Route::get('/current-session',              [CallController::class, 'getCurrentSession']);
            Route::get('/turn-credentials',             [TurnCredentialsController::class, 'index']);
            Route::get('/pending',                      [CallController::class, 'pending']);

            Route::post('/initiate',                    [CallController::class, 'initiateCall'])->middleware('throttle:10,1');
            Route::post('/{sessionId}/accept',          [CallController::class, 'acceptCall']);
            Route::post('/{sessionId}/reject',          [CallController::class, 'rejectCall']);
            Route::post('/{sessionId}/cancel',          [CallController::class, 'cancelCall']);
            Route::post('/{sessionId}/end',             [CallController::class, 'endCall']);
            Route::post('/{sessionId}/ice-candidate',   [CallController::class, 'sendIceCandidate']);
            Route::post('/{sessionId}/sdp',             [CallController::class, 'updateSdp']);
        });

        // 1-on-1 Real-Time Chat Consultations
        Route::prefix('chat')->group(function () {
            Route::get('/sessions/user',            [ChatController::class, 'getUserSessions']);
            Route::get('/sessions/astrologer',      [ChatController::class, 'getAstrologerSessions']);
            Route::get('/sessions/current',         [ChatController::class, 'getCurrentAcceptedSession']);
            Route::get('/sessions',                 [ChatController::class, 'getSessions']);
            Route::get('/current-session',          [ChatController::class, 'getCurrentSession']);
            Route::get('/{sessionId}/messages',     [ChatController::class, 'getMessages']);

            Route::post('/upload-attachment',       [ChatController::class, 'uploadAttachment']);
            Route::post('/initiate',                [ChatController::class, 'initiateChat']);
            Route::post('/{sessionId}/accept',      [ChatController::class, 'acceptChat']);
            Route::post('/{sessionId}/reject',      [ChatController::class, 'rejectChat']);
            Route::post('/{sessionId}/cancel',      [ChatController::class, 'cancelChat']);
            Route::post('/{sessionId}/end',         [ChatController::class, 'endChat']);
            Route::post('/{sessionId}/message',     [ChatController::class, 'sendMessage']);
            Route::post('/{sessionId}/read',        [ChatController::class, 'markAsRead']);
            Route::post('/{sessionId}/sync-status', [ChatController::class, 'syncStatus']);
        });

        // Chat Assistance (AI / Assistant Layer)
        Route::prefix('chat-assistance')->group(function () {
            Route::post('/initiate',                [ChatAssistanceController::class, 'initiate']);
            Route::post('/{sessionId}/message',     [ChatAssistanceController::class, 'sendMessage']);
            Route::get('/{sessionId}/messages',     [ChatAssistanceController::class, 'getMessages']);
            Route::post('/{sessionId}/sync-status', [ChatAssistanceController::class, 'syncStatus']);
            Route::get('/astrologer/status',        [ChatAssistanceController::class, 'getAstrologerStatus']);
            Route::get('/sessions',                 [ChatAssistanceController::class, 'getSessions']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 5. STATIC PAGES & POLICY INFORMATION
    |--------------------------------------------------------------------------
    */
    Route::get('/static-pages',             [StaticPageController::class, 'index'])->middleware('throttle:general');
    Route::get('/static-pages/{type}',      [StaticPageController::class, 'show'])->middleware('throttle:general');
    Route::get('/faqs',                     [StaticPageController::class, 'getFaqs'])->middleware('throttle:general');
    Route::get('/privacy-policy',           [StaticPageController::class, 'getPrivacyPolicy'])->middleware('throttle:general');
    Route::get('/terms-and-conditions',     [StaticPageController::class, 'getTermsAndConditions'])->middleware('throttle:general');
    Route::get('/payment-policy',           [StaticPageController::class, 'getPaymentPolicy'])->middleware('throttle:general');
    Route::get('/about-us',                 [StaticPageController::class, 'getAboutUs'])->middleware('throttle:general');
    Route::get('/customer-support',         [StaticPageController::class, 'getCustomerSupport'])->middleware('throttle:general');

    /*
    |--------------------------------------------------------------------------
    | 6. USER FEEDBACK
    |--------------------------------------------------------------------------
    */
    Route::post('/feedback',        [FeedbackController::class, 'store'])->middleware(['auth:sanctum', 'throttle:general']);
    Route::get('/feedbacks',        [FeedbackController::class, 'index'])->middleware('throttle:general');
    Route::get('/feedbacks/{id}',   [FeedbackController::class, 'show'])->middleware('throttle:general');

    /*
    |--------------------------------------------------------------------------
    | 7. KUNDLI (Vedic Birth Chart Generation)
    |--------------------------------------------------------------------------
    */
    Route::prefix('kundli')->middleware(['auth:sanctum'])->group(function () {
        Route::post('/create',  [KundliController::class, 'store'])->middleware('throttle:general');
        Route::get('/',         [KundliController::class, 'index'])->middleware('throttle:general');
        Route::get('/{id}',     [KundliController::class, 'show'])->middleware('throttle:general');
        Route::put('/{id}',     [KundliController::class, 'update'])->middleware('throttle:general');
        Route::delete('/{id}',  [KundliController::class, 'destroy'])->middleware('throttle:general');
    });

});
