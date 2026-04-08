<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\UserController;

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

        // Static Pages Management
        Route::resource('static_pages', \App\Http\Controllers\Admin\StaticPageController::class)->names('admin.static_pages');

        // Founder Words Management
        Route::resource('founder_words', \App\Http\Controllers\Admin\FounderWordsController::class)->names('admin.founder_words');

        // Plan Management
        Route::resource('plans', \App\Http\Controllers\Admin\PlanController::class)->names('admin.plans')->except(['show']);
        Route::get('plans/subscriptions', [\App\Http\Controllers\Admin\SubscriptionController::class, 'index'])->name('admin.plans.subscriptions');

        // Reports & Analytics
        Route::get('reports', function() { return view('admin.reports.index'); })->name('admin.reports.index');

        // Settings
        Route::get('settings', function() { return view('admin.settings.index'); })->name('admin.settings.index');

        Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');
    });
});
