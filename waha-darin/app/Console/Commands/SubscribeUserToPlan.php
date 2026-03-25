<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\Subscription;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SubscribeUserToPlan extends Command
{
    protected $signature = 'subscription:subscribe-user
                            {--email= : User email to subscribe}
                            {--plan= : Plan ID (optional, defaults to first available plan)}';

    protected $description = 'Subscribe a user by email to a plan (active subscription).';

    public function handle()
    {
        $email = $this->option('email') ?: $this->ask('User email');
        if (! $email) {
            $this->error('Email is required.');
            return 1;
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("User not found: {$email}");
            return 1;
        }

        $planId = $this->option('plan');
        $plan = $planId
            ? Plan::find($planId)
            : Plan::where('books_quota', '>', 0)->orderBy('id')->first();

        if (! $plan) {
            $this->error('No plan found. Create plans first or pass --plan=ID.');
            return 1;
        }

        $months = 12;
        $subscription = Subscription::where('user_id', $user->id)->first();
        if (! $subscription) {
            $subscription = new Subscription();
        }

        $subscription->user_id = $user->id;
        $subscription->plan_id = $plan->id;
        $subscription->start = Carbon::now();
        $subscription->end = Carbon::now()->addMonths($months);
        $quota = (int) $plan->books_quota;
        $subscription->quote = array_fill(0, $months + 1, $quota);
        $subscription->status = 'active';
        $subscription->transaction_amount = $plan->price;
        $subscription->transaction_date = Carbon::now();
        $subscription->save();

        $this->info("Subscribed user {$user->email} (id {$user->id}) to plan \"{$plan->name}\" (id {$plan->id}) until {$subscription->end->toDateString()}.");
        return 0;
    }
}
