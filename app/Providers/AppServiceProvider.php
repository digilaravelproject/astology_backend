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
        // Automatically handle initiated chat cancellations when a user goes offline/leaves presence channel
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Broadcasting\Events\PresenceChannelMemberLeft::class,
            [\App\Services\PresenceService::class, 'handleMemberLeft']
        );
    }
}
