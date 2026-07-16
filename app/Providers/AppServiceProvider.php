<?php

namespace App\Providers;

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
    }
}
