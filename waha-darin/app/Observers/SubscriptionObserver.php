<?php

namespace App\Observers;

use App\Mail\subscription\DeactivatedSubscription;
use App\Mail\subscription\ExpiredSubscription;
use App\Mail\subscription\PendingSubscription;
use App\Mail\subscription\StartSubscription;
use App\Models\Subscription;
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
        $status = strtolower((string) $subscription->status);
        if ($status === 'pending') {
            $subscription->loadMissing('user');
            try {
                Mail::to($subscription->user)->send(new PendingSubscription($subscription));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Subscription pending email failed: ' . $e->getMessage());
            }
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

        $user = $subscription->user;
        if ($status === 'active') {
            $this->sendMail(function () use ($user, $subscription) {
                Mail::to($user)->send(new StartSubscription($subscription));
            });
        } elseif ($status === 'pending') {
            $this->sendMail(function () use ($user, $subscription) {
                Mail::to($user)->send(new PendingSubscription($subscription));
            });
        } elseif ($status === 'expired') {
            $this->sendMail(function () use ($user, $subscription) {
                Mail::to($user)->send(new ExpiredSubscription($subscription));
            });
        } elseif (in_array($status, ['deactivated', 'inactive'], true)) {
            $this->sendMail(function () use ($user, $subscription) {
                Mail::to($user)->send(new DeactivatedSubscription($subscription));
            });
        }
    }

    /**
     * Handle the subscription "deleted" event.
     *
     * @param  \App\Models\Subscription  $subscription
     * @return void
     */
    private function sendMail(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Subscription email failed: ' . $e->getMessage());
        }
    }

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
