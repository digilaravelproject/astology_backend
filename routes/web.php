<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\GiftController;
use App\Http\Controllers\Admin\GiftTransactionController;
use App\Http\Controllers\Admin\FeedbackController;

Route::get('/', function () {
    // return view('welcome');
    return redirect()->route('admin.login');
});

Route::get('/admin', function () {
    return redirect()->route('admin.login');
});
// Admin Routes
Route::prefix('admin')->group(function () {
    // Login routes (no auth middleware)
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AuthController::class, 'login'])->name('admin.login.submit');

    // Protected routes
    Route::middleware('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

        // User management routes
        Route::resource('users', UserController::class)->names('admin.users');
        Route::post('users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('admin.users.toggle-status');
        Route::get('users-wallet', [\App\Http\Controllers\Admin\WalletController::class, 'index'])->name('admin.users.wallet');
        Route::post('users-wallet/topup', [\App\Http\Controllers\Admin\WalletController::class, 'topup'])->name('admin.users.wallet.topup');
        Route::get('users-wallet/{user}/transactions', [\App\Http\Controllers\Admin\WalletController::class, 'transactions'])->name('admin.users.wallet.transactions');
        Route::get('users-wallet/export', [\App\Http\Controllers\Admin\WalletController::class, 'exportCsv'])->name('admin.users.wallet.export');
        Route::get('users-referrals', function() { return view('admin.users.referrals'); })->name('admin.users.referrals');

        // Astrologer Management
        // Legacy / additional pages (keep if still used elsewhere)
        Route::get('astrologers/performance', function() { return view('admin.astrologers.performance'); })->name('admin.astrologers.performance');
        Route::get('astrologers/reviews', [\App\Http\Controllers\Admin\AstrologerReviewController::class, 'index'])->name('admin.astrologers.reviews');
        Route::post('astrologers/reviews/{review}/reply', [\App\Http\Controllers\Admin\AstrologerReviewController::class, 'reply'])->name('admin.astrologers.reviews.reply');
        Route::delete('astrologers/reviews/{review}', [\App\Http\Controllers\Admin\AstrologerReviewController::class, 'destroy'])->name('admin.astrologers.reviews.destroy');
        Route::get('astrologers/community', [\App\Http\Controllers\Admin\AstrologerCommunityController::class, 'index'])->name('admin.astrologers.community');
        Route::get('astrologers/reported', [\App\Http\Controllers\Admin\AstrologerCommunityController::class, 'reported'])->name('admin.astrologers.reported');
        Route::post('astrologers/reported/{community}/resolve', [\App\Http\Controllers\Admin\AstrologerCommunityController::class, 'resolveReport'])->name('admin.astrologers.reported.resolve');
        Route::post('astrologers/community/{community}/toggle-like', [\App\Http\Controllers\Admin\AstrologerCommunityController::class, 'toggleLike'])->name('admin.astrologers.community.toggle-like');
        Route::post('astrologers/community/{community}/toggle-block', [\App\Http\Controllers\Admin\AstrologerCommunityController::class, 'toggleBlock'])->name('admin.astrologers.community.toggle-block');
        Route::delete('astrologers/community/{community}', [\App\Http\Controllers\Admin\AstrologerCommunityController::class, 'destroy'])->name('admin.astrologers.community.destroy');
        Route::get('astrologers/live', function() { return view('admin.astrologers.live'); })->name('admin.astrologers.live');
        
        // Gallery Management
        Route::prefix('astrologers/gallery')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AstrologerGalleryController::class, 'index'])->name('admin.astrologers.gallery.index');
            Route::get('/{astrologerId}', [\App\Http\Controllers\Admin\AstrologerGalleryController::class, 'show'])->name('admin.astrologers.gallery.show');
            Route::post('/{id}/approve', [\App\Http\Controllers\Admin\AstrologerGalleryController::class, 'approve'])->name('admin.astrologers.gallery.approve');
            Route::post('/{id}/disapprove', [\App\Http\Controllers\Admin\AstrologerGalleryController::class, 'disapprove'])->name('admin.astrologers.gallery.disapprove');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\AstrologerGalleryController::class, 'destroy'])->name('admin.astrologers.gallery.destroy');
        });

        // Live Sessions Management
        Route::prefix('astrologers/live-sessions')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\LiveSessionController::class, 'index'])->name('admin.astrologers.live-sessions.index');
            Route::get('/{id}', [\App\Http\Controllers\Admin\LiveSessionController::class, 'show'])->name('admin.astrologers.live-sessions.show');
            Route::post('/{id}/status', [\App\Http\Controllers\Admin\LiveSessionController::class, 'updateStatus'])->name('admin.astrologers.live-sessions.update-status');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\LiveSessionController::class, 'destroy'])->name('admin.astrologers.live-sessions.destroy');
        });

        Route::resource('astrologers', \App\Http\Controllers\Admin\AstrologerController::class)->names('admin.astrologers');

        // Order Management
        Route::prefix('orders')->group(function() {
            Route::get('/', [OrderController::class, 'index'])->name('admin.orders.index');
            Route::get('/create', [OrderController::class, 'create'])->name('admin.orders.create');
            Route::post('/', [OrderController::class, 'store'])->name('admin.orders.store');
            Route::get('/{type}/{id}', [OrderController::class, 'show'])->where('type', 'call|chat')->name('admin.orders.show');
            Route::delete('/{type}/{id}', [OrderController::class, 'destroy'])->where('type', 'call|chat')->name('admin.orders.destroy');
            Route::get('/by-astrologer', [OrderController::class, 'byAstrologer'])->name('admin.orders.by-astrologer');
            Route::get('/by-astrologer/{provider}', [OrderController::class, 'providerOrders'])->name('admin.orders.by-astrologer.provider');
        });

        // Blog Management
        Route::resource('blogs', \App\Http\Controllers\Admin\BlogController::class)->names('admin.blogs');

        // Matrimony Management
        Route::resource('matrimonies', \App\Http\Controllers\Admin\MatrimonyController::class)->names('admin.matrimonies');
        Route::post('matrimonies/{id}/toggle-status', [\App\Http\Controllers\Admin\MatrimonyController::class, 'toggleStatus'])->name('admin.matrimonies.toggle-status');
        // Remedy Management
        Route::resource('remedies', \App\Http\Controllers\Admin\RemedyController::class)->names('admin.remedies');
        Route::post('remedies/{id}/toggle-status', [\App\Http\Controllers\Admin\RemedyController::class, 'toggleStatus'])->name('admin.remedies.toggle-status');

        // Training Videos Management
        Route::resource('training_videos', \App\Http\Controllers\Admin\TrainingVideoController::class)->names('admin.training_videos');

        // Gift Management
        Route::resource('gifts', GiftController::class)->names('admin.gifts');
        Route::get('gift-transactions', [GiftTransactionController::class, 'index'])->name('admin.gift_transactions.index');
        Route::get('gift-transactions/{transaction}', [GiftTransactionController::class, 'show'])->name('admin.gift_transactions.show');
        Route::delete('gift-transactions/{transaction}', [GiftTransactionController::class, 'destroy'])->name('admin.gift_transactions.destroy');

        // Feedback Management
        Route::resource('feedbacks', FeedbackController::class)->names('admin.feedbacks')->only(['index', 'show', 'destroy']);

        // Static Pages Management
        Route::resource('static_pages', \App\Http\Controllers\Admin\StaticPageController::class)->names('admin.static_pages');

        // Founder Words Management
        Route::resource('founder_words', \App\Http\Controllers\Admin\FounderWordsController::class)->names('admin.founder_words');

        // Plan Management
        Route::resource('plans', \App\Http\Controllers\Admin\PlanController::class)->names('admin.plans')->except(['show']);
        Route::get('plans/subscriptions', [\App\Http\Controllers\Admin\SubscriptionController::class, 'index'])->name('admin.plans.subscriptions');

        // Astrologer Bank Accounts Management
        Route::prefix('astrologer-bank-accounts')->group(function() {
            Route::get('/', [\App\Http\Controllers\Admin\AstrologerBankAccountController::class, 'index'])->name('admin.astrologer-bank-accounts.index');
            Route::get('/create', [\App\Http\Controllers\Admin\AstrologerBankAccountController::class, 'create'])->name('admin.astrologer-bank-accounts.create');
            Route::post('/', [\App\Http\Controllers\Admin\AstrologerBankAccountController::class, 'store'])->name('admin.astrologer-bank-accounts.store');
            Route::get('/{id}', [\App\Http\Controllers\Admin\AstrologerBankAccountController::class, 'show'])->name('admin.astrologer-bank-accounts.show');
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\AstrologerBankAccountController::class, 'edit'])->name('admin.astrologer-bank-accounts.edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\AstrologerBankAccountController::class, 'update'])->name('admin.astrologer-bank-accounts.update');
            Route::post('/{id}/toggle-verification', [\App\Http\Controllers\Admin\AstrologerBankAccountController::class, 'toggleVerification'])->name('admin.astrologer-bank-accounts.toggle-verification');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\AstrologerBankAccountController::class, 'destroy'])->name('admin.astrologer-bank-accounts.destroy');
        });

        // Astrologer Phone Numbers Management
        Route::prefix('astrologer-phone-numbers')->group(function() {
            Route::get('/', [\App\Http\Controllers\Admin\AstrologerPhoneNumberController::class, 'index'])->name('admin.astrologer-phone-numbers.index');
            Route::get('/{id}', [\App\Http\Controllers\Admin\AstrologerPhoneNumberController::class, 'show'])->name('admin.astrologer-phone-numbers.show');
            Route::post('/{id}/toggle-verification', [\App\Http\Controllers\Admin\AstrologerPhoneNumberController::class, 'toggleVerification'])->name('admin.astrologer-phone-numbers.toggle-verification');
            Route::post('/{id}/set-default', [\App\Http\Controllers\Admin\AstrologerPhoneNumberController::class, 'setDefault'])->name('admin.astrologer-phone-numbers.set-default');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\AstrologerPhoneNumberController::class, 'destroy'])->name('admin.astrologer-phone-numbers.destroy');
        });

        // Notices Management
        Route::resource('notices', \App\Http\Controllers\Admin\NoticeController::class)->names('admin.notices');
        Route::post('notices/{id}/toggle-status', [\App\Http\Controllers\Admin\NoticeController::class, 'toggleStatus'])->name('admin.notices.toggle-status');

        // App Notifications Management
        Route::prefix('app-notifications')->group(function() {
            Route::get('/', [\App\Http\Controllers\Admin\AppNotificationController::class, 'index'])->name('admin.app-notifications.index');
            Route::get('/{id}', [\App\Http\Controllers\Admin\AppNotificationController::class, 'show'])->name('admin.app-notifications.show');
            Route::post('/{id}/mark-read', [\App\Http\Controllers\Admin\AppNotificationController::class, 'markAsRead'])->name('admin.app-notifications.mark-read');
            Route::post('/{id}/mark-unread', [\App\Http\Controllers\Admin\AppNotificationController::class, 'markAsUnread'])->name('admin.app-notifications.mark-unread');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\AppNotificationController::class, 'destroy'])->name('admin.app-notifications.destroy');
            Route::delete('/bulk/delete-read', [\App\Http\Controllers\Admin\AppNotificationController::class, 'bulkDeleteRead'])->name('admin.app-notifications.bulk-delete-read');
        });

        // Wallet Transactions Management
        Route::prefix('wallet-transactions')->group(function() {
            Route::get('/', [\App\Http\Controllers\Admin\WalletTransactionController::class, 'index'])->name('admin.wallet-transactions.index');
            Route::get('/{id}', [\App\Http\Controllers\Admin\WalletTransactionController::class, 'show'])->name('admin.wallet-transactions.show');
            Route::post('/{id}/update-status', [\App\Http\Controllers\Admin\WalletTransactionController::class, 'updateStatus'])->name('admin.wallet-transactions.update-status');
            Route::post('/{id}/refund', [\App\Http\Controllers\Admin\WalletTransactionController::class, 'refund'])->name('admin.wallet-transactions.refund');
            Route::post('/wallet/{walletId}/adjust', [\App\Http\Controllers\Admin\WalletTransactionController::class, 'adjust'])->name('admin.wallet-transactions.adjust');
            Route::get('/export', [\App\Http\Controllers\Admin\WalletTransactionController::class, 'export'])->name('admin.wallet-transactions.export');
        });

        // Kundli Management
        Route::resource('kundlis', \App\Http\Controllers\Admin\KundliController::class)->names('admin.kundlis');

        // Reports & Analytics
        Route::get('reports', function() { return view('admin.reports.index'); })->name('admin.reports.index');

        // Settings
        Route::get('settings', function() { return view('admin.settings.index'); })->name('admin.settings.index');

        Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');
    });
});
