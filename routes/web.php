<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
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
        Route::resource('astrologers', \App\Http\Controllers\Admin\AstrologerController::class)->names('admin.astrologers');

        // Legacy / additional pages (keep if still used elsewhere)
        Route::get('astrologers/performance', function() { return view('admin.astrologers.performance'); })->name('admin.astrologers.performance');
        Route::get('astrologers/reviews', function() { return view('admin.astrologers.reviews'); })->name('admin.astrologers.reviews');
        Route::get('astrologers/live', function() { return view('admin.astrologers.live'); })->name('admin.astrologers.live');

        // Order Management
        Route::prefix('orders')->group(function() {
            Route::get('/', function() { return view('admin.orders.index'); })->name('admin.orders.index');
            Route::get('/by-astrologer', function() { return view('admin.orders.by-astrologer'); })->name('admin.orders.by-astrologer');
        });

        // Blog Management
        Route::resource('blogs', \App\Http\Controllers\Admin\BlogController::class)->names('admin.blogs');

        // Matrimony Management
        Route::resource('matrimonies', \App\Http\Controllers\Admin\MatrimonyController::class)->names('admin.matrimonies');
        Route::post('matrimonies/{id}/toggle-status', [\App\Http\Controllers\Admin\MatrimonyController::class, 'toggleStatus'])->name('admin.matrimonies.toggle-status');
        // Remedy Management
        Route::resource('remedies', \App\Http\Controllers\Admin\RemedyController::class)->names('admin.remedies');
        Route::post('remedies/{id}/toggle-status', [\App\Http\Controllers\Admin\RemedyController::class, 'toggleStatus'])->name('admin.remedies.toggle-status');
        // Plan Management
        Route::prefix('plans')->group(function() {
            Route::get('/', function() { return view('admin.plans.index'); })->name('admin.plans.index');
            Route::get('/create', function() { return view('admin.plans.create'); })->name('admin.plans.create');
            Route::get('/subscriptions', function() { return view('admin.plans.subscriptions'); })->name('admin.plans.subscriptions');
        });

        // Reports & Analytics
        Route::get('reports', function() { return view('admin.reports.index'); })->name('admin.reports.index');

        // Settings
        Route::get('settings', function() { return view('admin.settings.index'); })->name('admin.settings.index');

        Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');
    });
});
