<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Existing business schedulers
Schedule::command('app:expire-offers')->everyMinute();
Schedule::command('app:cleanup-chat-assistance')->daily();

// FCM & Push Notification Maintenance
Schedule::command('fcm:prune-devices')->daily();

// Live Session Reminders Scheduler
Schedule::command('live:send-scheduled-reminders')->everyMinute();

// Package Session Inactivity Watchdog
Schedule::command('packages:inactivity-watchdog')->everyMinute();

// Queue worker safety net (ensures queued push jobs process even if a standalone worker temporarily pauses)
Schedule::command('queue:work --stop-when-empty --tries=3 --timeout=60')
    ->everyMinute()
    ->withoutOverlapping();
