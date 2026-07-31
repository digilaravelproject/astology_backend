<?php

namespace App\Providers;

use App\Models\StaticPage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.footer', function ($view) {
            $footerPages = collect();

            try {
                if (Schema::hasTable('static_pages')) {
                    $footerPages = StaticPage::query()
                        ->where('is_active', true)
                        ->orderBy('title')
                        ->get(['type', 'title']);
                }
            } catch (\Throwable) {
                // Keep the public layout available while the database is unavailable or migrating.
            }

            $view->with('footerPages', $footerPages);
        });

        // Broadcast auth routes with Sanctum middleware (token auth)
        \Illuminate\Support\Facades\Broadcast::routes([
            'middleware' => ['auth:sanctum'],
            'prefix' => 'api/v1',
        ]);
        require base_path('routes/channels.php');

        // Automatically handle initiated chat cancellations when a user goes offline/leaves presence channel
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Broadcasting\Events\PresenceChannelMemberLeft::class,
            [\App\Services\PresenceService::class, 'handleMemberLeft']
        );

        // Register Astrologer model observer
        \App\Models\Astrologer::observe(\App\Observers\AstrologerObserver::class);

        // Dynamically override Razorpay configuration from Database settings
        try {
            if (class_exists(\App\Models\Setting::class)) {
                if ($key = \App\Models\Setting::get('razorpay_key')) {
                    config(['razorpay.key_id' => $key]);
                }
                if ($rawSecret = \App\Models\Setting::get('razorpay_secret')) {
                    try {
                        $secret = \Illuminate\Support\Facades\Crypt::decryptString($rawSecret);
                        config(['razorpay.key_secret' => $secret]);
                    } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                        config(['razorpay.key_secret' => $rawSecret]);
                    }
                }
            }
        } catch (\Exception $e) {
            // Prevent failure during early bootstrap (e.g., migrations)
        }
    }
}
