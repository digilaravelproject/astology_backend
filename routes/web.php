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

        Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');
    });
});
