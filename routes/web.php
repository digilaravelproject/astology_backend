<?php

use Illuminate\Support\Facades\Route;
use App\Models\StaticPage;
use App\Models\Blog;
use App\Models\Feedback;
use App\Models\Astrologer;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\Admin\{
    AuthController,
    DashboardController,
    UserController,
    WalletController as AdminWalletController,
    AstrologerController,
    AstrologerReviewController,
    AstrologerCommunityController,
    AstrologerGalleryController,
    AstrologerPricingController,
    AstrologerBankAccountController,
    AstrologerPhoneNumberController,
    LiveSessionController as AdminLiveSessionController,
    OrderController,
    LanguageController,
    BlogController as AdminBlogController,
    MatrimonyController as AdminMatrimonyController,
    RemedyController as AdminRemedyController,
    TrainingVideoController,
    GiftController as AdminGiftController,
    GiftTransactionController,
    FeedbackController as AdminFeedbackController,
    StaticPageController as AdminStaticPageController,
    FounderWordsController,
    PlanController as AdminPlanController,
    SubscriptionController,
    NoticeController as AdminNoticeController,
    PriceIncreaseLevelController,
    PriceIncreaseRequestController,
    AppNotificationController,
    AdminPushBroadcastController,
    WalletTransactionController,
    KundliController as AdminKundliController,
    SettingController,
    RateLimitController,
    AdminFcmSettingController,
    OfferController as AdminOfferController,
    AdminPackageController,
    AstrologerPayoutController as AdminAstrologerPayoutController,
    AstrologerPerformanceController,
    LiveAstrologerMonitorController,
    ReportAnalyticsController
};

/*
|--------------------------------------------------------------------------
| Public Website & Landing Page Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    $faq = StaticPage::where('type', 'faq')->where('is_active', true)->first();
    $blogs = Blog::where('is_active', true)->orderByDesc('created_at')->get();
    $feedbacks = Feedback::with('user')->orderByDesc('created_at')->get();
    $astrologers = Astrologer::with('user')
        ->orderByRaw("CASE WHEN status = 'approved' THEN 1 ELSE 2 END")
        ->orderByDesc('created_at')
        ->take(4)
        ->get();

    return view('welcome', compact('faq', 'blogs', 'feedbacks', 'astrologers'));
});

// Admin shortcut redirect
Route::get('/admin', function () {
    return redirect()->route('admin.login');
});

/*
|--------------------------------------------------------------------------
| Public Information & Policy Pages
|--------------------------------------------------------------------------
*/

Route::prefix('page')->group(function () {
    Route::get('/faq',                  [StaticPageController::class, 'faq'])->name('page.faq');
    Route::get('/privacy-policy',       [StaticPageController::class, 'privacyPolicy'])->name('page.privacy-policy');
    Route::get('/terms-and-conditions', [StaticPageController::class, 'termsConditions'])->name('page.terms-and-conditions');
    Route::get('/payment-policy',       [StaticPageController::class, 'paymentPolicy'])->name('page.payment-policy');
    Route::get('/about-us',             [StaticPageController::class, 'aboutUs'])->name('page.about-us');
    Route::get('/customer-support',     [StaticPageController::class, 'customerSupport'])->name('page.customer-support');
    Route::get('/contact-us',           [StaticPageController::class, 'contactUs'])->name('page.contact-us');
    Route::get('/{type}',               [StaticPageController::class, 'show'])->name('page.show');
});

// Direct SEO Policy routes
Route::get('/about-us', function () {
    $page = StaticPage::where('type', 'about_us')->where('is_active', true)->first();
    return view('about', compact('page'));
})->name('about');

Route::get('/privacy-policy', function () {
    $page = StaticPage::where('type', 'privacy_policy')->where('is_active', true)->first();
    return view('privacy', compact('page'));
})->name('privacy');

Route::get('/terms-and-conditions', function () {
    $page = StaticPage::where('type', 'terms_and_conditions')->where('is_active', true)->first();
    return view('terms', compact('page'));
})->name('terms');

Route::get('/support', function () {
    $page = StaticPage::where('type', 'customer_support')->where('is_active', true)->first();
    return view('support', compact('page'));
})->name('support');

Route::get('/payment-policy', function () {
    $page = StaticPage::where('type', 'payment_policy')->where('is_active', true)->first();
    return view('payment', compact('page'));
})->name('payment_policy');


/*
|--------------------------------------------------------------------------
| SUPER ADMIN PANEL ROUTES
|--------------------------------------------------------------------------
|
| All routes prefixed with /admin
|
*/

Route::prefix('admin')->group(function () {

    // ── Authentication ───────────────────────────────────────────────────
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AuthController::class, 'login'])->name('admin.login.submit');

    // ── Protected Admin Backoffice ───────────────────────────────────────
    Route::middleware('admin')->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
        Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');

        // User Management
        Route::resource('users', UserController::class)->names('admin.users');
        Route::post('users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('admin.users.toggle-status');
        Route::get('users-wallet', [AdminWalletController::class, 'index'])->name('admin.users.wallet');
        Route::post('users-wallet/topup', [AdminWalletController::class, 'topup'])->name('admin.users.wallet.topup');
        Route::get('users-wallet/{user}/transactions', [AdminWalletController::class, 'transactions'])->name('admin.users.wallet.transactions');
        Route::get('users-wallet/export', [AdminWalletController::class, 'exportCsv'])->name('admin.users.wallet.export');
        Route::get('users-referrals', function () { return view('admin.users.referrals'); })->name('admin.users.referrals');

        // Astrologer Management
        Route::get('astrologers/performance', [AstrologerPerformanceController::class, 'index'])->name('admin.astrologers.performance');
        Route::get('astrologers/live', [LiveAstrologerMonitorController::class, 'index'])->name('admin.astrologers.live');
        Route::get('astrologers/pricing', [AstrologerPricingController::class, 'index'])->name('admin.astrologers.pricing');
        Route::post('astrologers/pricing', [AstrologerPricingController::class, 'update'])->name('admin.astrologers.pricing.update');

        // Astrologer Reviews & Moderation
        Route::get('astrologers/reviews', [AstrologerReviewController::class, 'index'])->name('admin.astrologers.reviews');
        Route::post('astrologers/reviews/{review}/reply', [AstrologerReviewController::class, 'reply'])->name('admin.astrologers.reviews.reply');
        Route::delete('astrologers/reviews/{review}', [AstrologerReviewController::class, 'destroy'])->name('admin.astrologers.reviews.destroy');

        // Astrologer Community & Reported Accounts
        Route::get('astrologers/community', [AstrologerCommunityController::class, 'index'])->name('admin.astrologers.community');
        Route::get('astrologers/reported', [AstrologerCommunityController::class, 'reported'])->name('admin.astrologers.reported');
        Route::post('astrologers/reported/{community}/resolve', [AstrologerCommunityController::class, 'resolveReport'])->name('admin.astrologers.reported.resolve');
        Route::post('astrologers/community/{community}/toggle-like', [AstrologerCommunityController::class, 'toggleLike'])->name('admin.astrologers.community.toggle-like');
        Route::post('astrologers/community/{community}/toggle-block', [AstrologerCommunityController::class, 'toggleBlock'])->name('admin.astrologers.community.toggle-block');
        Route::delete('astrologers/community/{community}', [AstrologerCommunityController::class, 'destroy'])->name('admin.astrologers.community.destroy');

        // Astrologer Gallery Moderation
        Route::prefix('astrologers/gallery')->group(function () {
            Route::get('/', [AstrologerGalleryController::class, 'index'])->name('admin.astrologers.gallery.index');
            Route::get('/{astrologerId}', [AstrologerGalleryController::class, 'show'])->name('admin.astrologers.gallery.show');
            Route::post('/{id}/approve', [AstrologerGalleryController::class, 'approve'])->name('admin.astrologers.gallery.approve');
            Route::post('/{id}/disapprove', [AstrologerGalleryController::class, 'disapprove'])->name('admin.astrologers.gallery.disapprove');
            Route::delete('/{id}', [AstrologerGalleryController::class, 'destroy'])->name('admin.astrologers.gallery.destroy');
        });

        // Astrologer Live Streams
        Route::prefix('astrologers/live-sessions')->group(function () {
            Route::get('/', [AdminLiveSessionController::class, 'index'])->name('admin.astrologers.live-sessions.index');
            Route::get('/{id}', [AdminLiveSessionController::class, 'show'])->name('admin.astrologers.live-sessions.show');
            Route::post('/{id}/status', [AdminLiveSessionController::class, 'updateStatus'])->name('admin.astrologers.live-sessions.update-status');
            Route::delete('/{id}', [AdminLiveSessionController::class, 'destroy'])->name('admin.astrologers.live-sessions.destroy');
        });

        // Astrologer Phone Numbers Verification
        Route::prefix('astrologer-phone-numbers')->group(function () {
            Route::get('/', [AstrologerPhoneNumberController::class, 'index'])->name('admin.astrologer-phone-numbers.index');
            Route::get('/{id}', [AstrologerPhoneNumberController::class, 'show'])->name('admin.astrologer-phone-numbers.show');
            Route::post('/{id}/toggle-verification', [AstrologerPhoneNumberController::class, 'toggleVerification'])->name('admin.astrologer-phone-numbers.toggle-verification');
            Route::post('/{id}/set-default', [AstrologerPhoneNumberController::class, 'setDefault'])->name('admin.astrologer-phone-numbers.set-default');
            Route::delete('/{id}', [AstrologerPhoneNumberController::class, 'destroy'])->name('admin.astrologer-phone-numbers.destroy');
        });

        // Astrologer Bank Accounts Verification
        Route::prefix('astrologer-bank-accounts')->group(function () {
            Route::get('/', [AstrologerBankAccountController::class, 'index'])->name('admin.astrologer-bank-accounts.index');
            Route::get('/create', [AstrologerBankAccountController::class, 'create'])->name('admin.astrologer-bank-accounts.create');
            Route::post('/', [AstrologerBankAccountController::class, 'store'])->name('admin.astrologer-bank-accounts.store');
            Route::get('/{id}', [AstrologerBankAccountController::class, 'show'])->name('admin.astrologer-bank-accounts.show');
            Route::get('/{id}/edit', [AstrologerBankAccountController::class, 'edit'])->name('admin.astrologer-bank-accounts.edit');
            Route::put('/{id}', [AstrologerBankAccountController::class, 'update'])->name('admin.astrologer-bank-accounts.update');
            Route::post('/{id}/toggle-verification', [AstrologerBankAccountController::class, 'toggleVerification'])->name('admin.astrologer-bank-accounts.toggle-verification');
            Route::delete('/{id}', [AstrologerBankAccountController::class, 'destroy'])->name('admin.astrologer-bank-accounts.destroy');
        });

        // Astrologer Monthly Payouts & TDS Settlements
        Route::prefix('astrologer-payouts')->group(function () {
            Route::get('/', [AdminAstrologerPayoutController::class, 'index'])->name('admin.astrologer-payouts.index');
            Route::get('/{astrologerId}/context', [AdminAstrologerPayoutController::class, 'getContext'])->name('admin.astrologer-payouts.context');
            Route::post('/preview-tds', [AdminAstrologerPayoutController::class, 'previewTds'])->name('admin.astrologer-payouts.preview-tds');
            Route::post('/', [AdminAstrologerPayoutController::class, 'store'])->name('admin.astrologer-payouts.store');
            Route::get('/{id}/download-slip', [AdminAstrologerPayoutController::class, 'downloadSlip'])->name('admin.astrologer-payouts.download-slip');
        });

        // Core Astrologer CRUD
        Route::post('astrologers/{id}/status', [AstrologerController::class, 'updateStatus'])->name('admin.astrologers.status');
        Route::resource('astrologers', AstrologerController::class)->names('admin.astrologers');

        // Order Management (Call & Chat Consultations)
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('admin.orders.index');
            Route::get('/create', [OrderController::class, 'create'])->name('admin.orders.create');
            Route::post('/', [OrderController::class, 'store'])->name('admin.orders.store');
            Route::get('/{type}/{id}', [OrderController::class, 'show'])->where('type', 'call|chat')->name('admin.orders.show');
            Route::delete('/{type}/{id}', [OrderController::class, 'destroy'])->where('type', 'call|chat')->name('admin.orders.destroy');
            Route::get('/by-astrologer', [OrderController::class, 'byAstrologer'])->name('admin.orders.by-astrologer');
            Route::get('/by-astrologer/{provider}', [OrderController::class, 'providerOrders'])->name('admin.orders.by-astrologer.provider');
        });

        // Financial & Wallet Transactions
        Route::prefix('wallet-transactions')->group(function () {
            Route::get('/export', [WalletTransactionController::class, 'export'])->name('admin.wallet-transactions.export');
            Route::get('/', [WalletTransactionController::class, 'index'])->name('admin.wallet-transactions.index');
            Route::get('/{id}', [WalletTransactionController::class, 'show'])->name('admin.wallet-transactions.show');
            Route::get('/{id}/invoice', [WalletTransactionController::class, 'downloadInvoice'])->name('admin.wallet-transactions.download-invoice');
            Route::post('/{id}/update-status', [WalletTransactionController::class, 'updateStatus'])->name('admin.wallet-transactions.update-status');
            Route::post('/{id}/refund', [WalletTransactionController::class, 'refund'])->name('admin.wallet-transactions.refund');
            Route::post('/wallet/{walletId}/adjust', [WalletTransactionController::class, 'adjust'])->name('admin.wallet-transactions.adjust');
        });

        // Plans & Subscriptions
        Route::resource('plans', AdminPlanController::class)->names('admin.plans')->except(['show']);
        Route::get('plans/subscriptions', [SubscriptionController::class, 'index'])->name('admin.plans.subscriptions');

        // Packages Management
        Route::get('packages', [AdminPackageController::class, 'index'])->name('admin.packages.index');
        Route::post('packages', [AdminPackageController::class, 'store'])->name('admin.packages.store');
        Route::put('packages/{id}', [AdminPackageController::class, 'update'])->name('admin.packages.update');
        Route::delete('packages/{id}', [AdminPackageController::class, 'destroy'])->name('admin.packages.destroy');
        Route::post('packages/assign', [AdminPackageController::class, 'assignToAstrologer'])->name('admin.packages.assign');
        Route::delete('packages/override/{id}', [AdminPackageController::class, 'removeOverride'])->name('admin.packages.remove-override');

        // Offers Management
        Route::resource('offers', AdminOfferController::class)->names('admin.offers');
        Route::post('offers/{offer}/toggle-status', [AdminOfferController::class, 'toggleStatus'])->name('admin.offers.toggle-status');

        // Price Increase Tier Governance
        Route::prefix('price-increase-levels')->group(function () {
            Route::get('/', [PriceIncreaseLevelController::class, 'index'])->name('admin.price-increase-levels.index');
            Route::get('/create', [PriceIncreaseLevelController::class, 'create'])->name('admin.price-increase-levels.create');
            Route::post('/', [PriceIncreaseLevelController::class, 'store'])->name('admin.price-increase-levels.store');
            Route::get('/{id}/edit', [PriceIncreaseLevelController::class, 'edit'])->name('admin.price-increase-levels.edit');
            Route::put('/{id}', [PriceIncreaseLevelController::class, 'update'])->name('admin.price-increase-levels.update');
            Route::post('/{id}/toggle-status', [PriceIncreaseLevelController::class, 'toggleStatus'])->name('admin.price-increase-levels.toggle-status');
            Route::delete('/{id}', [PriceIncreaseLevelController::class, 'destroy'])->name('admin.price-increase-levels.destroy');
        });

        // Price Increase Requests
        Route::prefix('price-increase-requests')->group(function () {
            Route::get('/', [PriceIncreaseRequestController::class, 'index'])->name('admin.price-increase-requests.index');
            Route::get('/{id}', [PriceIncreaseRequestController::class, 'show'])->name('admin.price-increase-requests.show');
            Route::post('/{id}/approve', [PriceIncreaseRequestController::class, 'approve'])->name('admin.price-increase-requests.approve');
            Route::post('/{id}/reject', [PriceIncreaseRequestController::class, 'reject'])->name('admin.price-increase-requests.reject');
        });

        // Communication: In-App Notifications
        Route::prefix('app-notifications')->group(function () {
            Route::get('/', [AppNotificationController::class, 'index'])->name('admin.app-notifications.index');
            Route::get('/{id}', [AppNotificationController::class, 'show'])->name('admin.app-notifications.show');
            Route::post('/{id}/mark-read', [AppNotificationController::class, 'markAsRead'])->name('admin.app-notifications.mark-read');
            Route::post('/{id}/mark-unread', [AppNotificationController::class, 'markAsUnread'])->name('admin.app-notifications.mark-unread');
            Route::delete('/{id}', [AppNotificationController::class, 'destroy'])->name('admin.app-notifications.destroy');
            Route::delete('/bulk/delete-read', [AppNotificationController::class, 'bulkDeleteRead'])->name('admin.app-notifications.bulk-delete-read');
        });

        // Communication: Push Notification Broadcasts (FCM)
        Route::prefix('push-notifications')->group(function () {
            Route::get('/', [AdminPushBroadcastController::class, 'index'])->name('admin.push-notifications.index');
            Route::get('/create', [AdminPushBroadcastController::class, 'create'])->name('admin.push-notifications.create');
            Route::post('/', [AdminPushBroadcastController::class, 'store'])->name('admin.push-notifications.store');
            Route::get('/search-users', [AdminPushBroadcastController::class, 'searchUsers'])->name('admin.push-notifications.search-users');
            Route::delete('/{id}', [AdminPushBroadcastController::class, 'destroy'])->name('admin.push-notifications.destroy');
        });

        // Communication: Broadcast Notices
        Route::resource('notices', AdminNoticeController::class)->names('admin.notices');
        Route::post('notices/{id}/toggle-status', [AdminNoticeController::class, 'toggleStatus'])->name('admin.notices.toggle-status');

        // Content: Languages
        Route::resource('languages', LanguageController::class)->names('admin.languages');
        Route::post('languages/{id}/toggle-status', [LanguageController::class, 'toggleStatus'])->name('admin.languages.toggle-status');

        // Content: Blogs
        Route::resource('blogs', AdminBlogController::class)->names('admin.blogs');

        // Content: Matrimony Profiles
        Route::resource('matrimonies', AdminMatrimonyController::class)->names('admin.matrimonies');
        Route::post('matrimonies/{id}/toggle-status', [AdminMatrimonyController::class, 'toggleStatus'])->name('admin.matrimonies.toggle-status');

        // Content: Remedies
        Route::resource('remedies', AdminRemedyController::class)->names('admin.remedies');
        Route::post('remedies/{id}/toggle-status', [AdminRemedyController::class, 'toggleStatus'])->name('admin.remedies.toggle-status');

        // Content: Training Videos
        Route::resource('training_videos', TrainingVideoController::class)->names('admin.training_videos');

        // Content: Founder's Words
        Route::resource('founder_words', FounderWordsController::class)->names('admin.founder_words');

        // Content: Static Pages
        Route::resource('static_pages', AdminStaticPageController::class)->names('admin.static_pages');

        // Gifts & Microtransactions
        Route::resource('gifts', AdminGiftController::class)->names('admin.gifts');
        Route::get('gift-transactions', [GiftTransactionController::class, 'index'])->name('admin.gift_transactions.index');
        Route::get('gift-transactions/{transaction}', [GiftTransactionController::class, 'show'])->name('admin.gift_transactions.show');
        Route::delete('gift-transactions/{transaction}', [GiftTransactionController::class, 'destroy'])->name('admin.gift_transactions.destroy');

        // Kundlis (Birth Charts)
        Route::resource('kundlis', AdminKundliController::class)->names('admin.kundlis');

        // Feedbacks & Reviews Moderation
        Route::resource('feedbacks', AdminFeedbackController::class)->names('admin.feedbacks')->only(['index', 'show', 'destroy']);

        // Reports & Analytics (Intelligence Center)
        Route::get('reports', [ReportAnalyticsController::class, 'index'])->name('admin.reports.index');

        // Platform & System Settings
        Route::get('settings', [SettingController::class, 'index'])->name('admin.settings.index');
        Route::post('settings', [SettingController::class, 'update'])->name('admin.settings.update');
        Route::prefix('settings')->group(function () {
            Route::post('operators', [SettingController::class, 'storeOperator'])->name('admin.settings.operators.store');
            Route::put('operators/{id}', [SettingController::class, 'updateOperator'])->name('admin.settings.operators.update');
            Route::delete('operators/{id}', [SettingController::class, 'destroyOperator'])->name('admin.settings.operators.destroy');
            Route::get('rate-limits', [RateLimitController::class, 'index'])->name('admin.settings.rate-limits');
            Route::post('rate-limits', [RateLimitController::class, 'update'])->name('admin.settings.rate-limits.update');

            // Firebase FCM Settings & Diagnostics
            Route::get('firebase', [AdminFcmSettingController::class, 'index'])->name('admin.settings.firebase.index');
            Route::post('firebase', [AdminFcmSettingController::class, 'update'])->name('admin.settings.firebase.update');
            Route::post('firebase/upload', [AdminFcmSettingController::class, 'uploadServiceAccount'])->name('admin.settings.firebase.upload');
            Route::post('firebase/test', [AdminFcmSettingController::class, 'testConnection'])->name('admin.settings.firebase.test');
        });
    });
});
