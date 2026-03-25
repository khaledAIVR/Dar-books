<?php

namespace App\Observers;

use App\Mail\subscription\BeforeEndSubscription;
use App\Mail\subscription\DeactivatedSubscription;
use App\Mail\subscription\EndSubscription;
use App\Mail\subscription\ExpiredSubscription;
use App\Mail\subscription\PendingSubscription;
use App\Mail\subscription\StartSubscription;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class SubscriptionObserver
{
    /**
     * Handle the subscription "created" event.
     *
     * @param  \App\Models\Subscription  $subscription
     * @return void
     */
    public function created(Subscription $subscription)
    {
        // New subscriptions start as pending until bank transfer is reviewed.
        // Send a pending notification to the user.
        $status = strtolower((string) $subscription->status);
        if ($status === 'pending') {
            $subscription->loadMissing('user');
            Mail::to($subscription->user)->send(new PendingSubscription($subscription));
        }
    }

    /**
     * Handle the subscription "updated" event.
     *
     * @param  \App\Models\Subscription  $subscription
     * @return void
     */
    public function updated(Subscription $subscription)
    {

        if (!$subscription->isDirty('status')) {
            return;
        }

        $subscription->loadMissing('user');
        $status = strtolower((string) $subscription->status);

        if ($status === 'active') {
            $startSubscription      = new StartSubscription($subscription);
            $endSubscription        = new EndSubscription($subscription);
            $beforeEndSubscription  = new BeforeEndSubscription($subscription);
            Mail::to($subscription->user)->send($startSubscription);
            Mail::to($subscription->user)->later($subscription->end, $endSubscription);
            Mail::to($subscription->user)->later($subscription->end->subWeeks(2), $beforeEndSubscription);
            return;
        }

        if ($status === 'pending') {
            Mail::to($subscription->user)->send(new PendingSubscription($subscription));
            return;
        }

        if ($status === 'expired') {
            Mail::to($subscription->user)->send(new ExpiredSubscription($subscription));
            return;
        }

        if (in_array($status, ['deactivated', 'inactive'], true)) {
            Mail::to($subscription->user)->send(new DeactivatedSubscription($subscription));
            return;
        }
    }

    /**
     * Handle the subscription "deleted" event.
     *
     * @param  \App\Models\Subscription  $subscription
     * @return void
     */
    public function deleted(Subscription $subscription)
    {
        //
    }

    /**
     * Handle the subscription "restored" event.
     *
     * @param  \App\Models\Subscription  $subscription
     * @return void
     */
    public function restored(Subscription $subscription)
    {
        //
    }

    /**
     * Handle the subscription "force deleted" event.
     *
     * @param  \App\Models\Subscription  $subscription
     * @return void
     */
    public function forceDeleted(Subscription $subscription)
    {
        //
    }
}
