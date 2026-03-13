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
        Route::get('users-wallet', function() { return view('admin.users.wallet'); })->name('admin.users.wallet');
        Route::get('users-referrals', function() { return view('admin.users.referrals'); })->name('admin.users.referrals');

        // Astrologer Management
        Route::prefix('astrologers')->group(function() {
            Route::get('/', function() { return view('admin.astrologers.index'); })->name('admin.astrologers.index');
            Route::get('/performance', function() { return view('admin.astrologers.performance'); })->name('admin.astrologers.performance');
            Route::get('/reviews', function() { return view('admin.astrologers.reviews'); })->name('admin.astrologers.reviews');
            Route::get('/live', function() { return view('admin.astrologers.live'); })->name('admin.astrologers.live');
        });

        // Order Management
        Route::prefix('orders')->group(function() {
            Route::get('/', function() { return view('admin.orders.index'); })->name('admin.orders.index');
            Route::get('/by-astrologer', function() { return view('admin.orders.by-astrologer'); })->name('admin.orders.by-astrologer');
        });

        // Blog Management
        Route::prefix('blogs')->group(function() {
            Route::get('/', function() { return view('admin.blogs.index'); })->name('admin.blogs.index');
            Route::get('/create', function() { return view('admin.blogs.create'); })->name('admin.blogs.create');
        });

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
