<?php

namespace App\Providers;

use App\Models\BorrowOrder;
use App\Models\Subscription;
use App\Notifications\VerifyEmail;
use App\Observers\BorrowOrderObserver;
use App\Observers\SubscriptionObserver;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Render (and similar) terminate TLS at the edge; force HTTPS for asset(), route(), etc.
        if ($this->app->environment('production') || env('FORCE_HTTPS_URLS', false)) {
            URL::forceScheme('https');
        }

        Event::listen(NotificationSent::class, function (NotificationSent $event) {
            if (! $event->notification instanceof VerifyEmail) {
                return;
            }
            $to = method_exists($event->notifiable, 'routeNotificationForMail')
                ? $event->notifiable->routeNotificationForMail()
                : ($event->notifiable->email ?? null);
            Log::info('VerifyEmail notification sent via '.$event->channel, [
                'to' => $to,
                'user_id' => $event->notifiable->getKey() ?? null,
            ]);
        });

        Subscription::observe(SubscriptionObserver::class);
        BorrowOrder::observe(BorrowOrderObserver::class);
    }
}
