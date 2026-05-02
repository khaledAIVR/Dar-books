<?php

namespace App\Providers;

use App\Mail\Transport\BrevoTransport;
use App\Models\BorrowOrder;
use App\Models\Subscription;
use App\Notifications\VerifyEmail;
use App\Observers\BorrowOrderObserver;
use App\Observers\SubscriptionObserver;
use GuzzleHttp\Client as GuzzleClient;
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
        $this->app->booted(function () {
            $this->registerBrevoMailTransport();
        });
    }

    private function registerBrevoMailTransport(): void
    {
        $this->app->make('mail.manager')->extend('brevo', function () {
            $key = trim((string) config('services.brevo.key'));
            $client = new GuzzleClient([
                'timeout' => (float) config('services.brevo.timeout', 60),
                'connect_timeout' => (float) config('services.brevo.connect_timeout', 20),
            ]);

            return new BrevoTransport($client, $key);
        });
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
