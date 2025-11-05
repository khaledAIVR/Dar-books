<?php

namespace App\Providers;

use App\Models\BorrowOrder;
use App\Models\Subscription;
use App\Observers\BorrowOrderObserver;
use App\Observers\SubscriptionObserver;
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
        //
        Subscription::observe(SubscriptionObserver::class);
        BorrowOrder::observe(BorrowOrderObserver::class);
    }
}
