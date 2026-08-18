<?php

namespace App\Providers;

use App\Events\CallAccepted;
use App\Events\CallDismissed;
use App\Events\CallEnded;
use App\Events\CallInitiated;
use App\Events\ChatAccepted;
use App\Events\ChatDismissed;
use App\Events\ChatEnded;
use App\Events\ChatInitiated;
use App\Events\MessageSent;
use App\Events\PackageSessionTerminated;
use App\Listeners\SendCallPushNotificationListener;
use App\Listeners\SendChatInitiatedPushListener;
use App\Listeners\SendMessagePushNotificationListener;
use App\Listeners\SendPackageSessionNotificationListener;
use App\Listeners\SendSessionAcceptedPushListener;
use App\Listeners\SendSessionDismissedPushListener;
use App\Listeners\SendSessionEndedPushListener;
use App\Models\Astrologer;
use App\Models\Setting;
use App\Models\StaticPage;
use App\Observers\AstrologerObserver;
use App\Services\PresenceService;
use Illuminate\Broadcasting\Events\PresenceChannelMemberLeft;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

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
        $this->bootViewComposers();
        $this->bootBroadcasting();
        $this->bootEventListeners();
        $this->bootObservers();
        $this->bootDynamicConfigs();
    }

    /**
     * Register view composers for frontend templates.
     */
    protected function bootViewComposers(): void
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
            } catch (Throwable) {
                // Keep the public layout available while the database is unavailable or migrating.
            }

            $view->with('footerPages', $footerPages);
        });
    }

    /**
     * Configure WebSocket & real-time broadcast auth routes.
     */
    protected function bootBroadcasting(): void
    {
        // Broadcast auth routes with Sanctum middleware (token auth)
        Broadcast::routes([
            'middleware' => ['auth:sanctum'],
            'prefix'     => 'api/v1',
        ]);

        require base_path('routes/channels.php');
    }

    /**
     * Register global event listeners.
     */
    protected function bootEventListeners(): void
    {
        // Handle initiated chat cancellations when a user goes offline/leaves presence channel
        Event::listen(
            PresenceChannelMemberLeft::class,
            [PresenceService::class, 'handleMemberLeft']
        );

        // 1. Initiation Listeners
        Event::listen(
            CallInitiated::class,
            SendCallPushNotificationListener::class
        );
        Event::listen(
            ChatInitiated::class,
            SendChatInitiatedPushListener::class
        );

        // 2. Chat Message Push Listener
        Event::listen(
            MessageSent::class,
            SendMessagePushNotificationListener::class
        );

        // 3. Acceptance Listeners (Notify Consumer User)
        Event::listen(
            ChatAccepted::class,
            SendSessionAcceptedPushListener::class
        );
        Event::listen(
            CallAccepted::class,
            SendSessionAcceptedPushListener::class
        );

        // 4. Session Ended & Billing Summary Listeners (Notify Both User & Astrologer)
        Event::listen(
            ChatEnded::class,
            SendSessionEndedPushListener::class
        );
        Event::listen(
            CallEnded::class,
            SendSessionEndedPushListener::class
        );

        // 5. Dismissed / Rejected / Cancelled Listeners (Notify Appropriate Party)
        Event::listen(
            ChatDismissed::class,
            SendSessionDismissedPushListener::class
        );
        Event::listen(
            CallDismissed::class,
            SendSessionDismissedPushListener::class
        );

        // 6. Prepaid Package Session Termination Listener
        Event::listen(
            PackageSessionTerminated::class,
            SendPackageSessionNotificationListener::class
        );
    }

    /**
     * Register Eloquent model observers.
     */
    protected function bootObservers(): void
    {
        Astrologer::observe(AstrologerObserver::class);
    }

    /**
     * Dynamically override third-party configs from Database settings table.
     */
    protected function bootDynamicConfigs(): void
    {
        try {
            if (class_exists(Setting::class) && Schema::hasTable('settings')) {
                if ($key = Setting::get('razorpay_key')) {
                    config(['razorpay.key_id' => $key]);
                }

                if ($rawSecret = Setting::get('razorpay_secret')) {
                    try {
                        $secret = Crypt::decryptString($rawSecret);
                        config(['razorpay.key_secret' => $secret]);
                    } catch (DecryptException) {
                        config(['razorpay.key_secret' => $rawSecret]);
                    }
                }
            }
        } catch (Throwable) {
            // Prevent failure during early bootstrap or migrations
        }
    }
}
