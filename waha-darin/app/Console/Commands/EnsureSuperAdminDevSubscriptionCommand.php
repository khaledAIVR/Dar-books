<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\Subscription;
use App\User;
use Illuminate\Console\Command;

class EnsureSuperAdminDevSubscriptionCommand extends Command
{
    protected $signature = 'superadmin:ensure-dev-subscription
        {--force : Replace an existing subscription for this user (same user_id)}';

    protected $description = 'Create or refresh an active subscription row for the configured super admin (so /api/user and subscription UIs show a plan).';

    public function handle(): int
    {
        $user = $this->resolveSuperAdminUser();
        if ($user === null) {
            $this->error('No super admin user found. Set SUPER_ADMIN_EMAIL to an existing user email, or SUPER_ADMIN_ID.');

            return 1;
        }

        $planId = (int) config('superadmin.dev_subscription_plan_id', 3);
        $plan = Plan::query()->find($planId);
        if ($plan === null) {
            $plan = Plan::query()->where('books_quota', '>', 0)->orderBy('id')->first();
        }
        if ($plan === null) {
            $this->error('No suitable plan found in `plans` (need at least one row).');

            return 1;
        }

        $existing = Subscription::query()->where('user_id', $user->id)->first();
        if ($existing !== null && ! $this->option('force')) {
            $existing->plan_id = $plan->id;
            $existing->start = now();
            $existing->end = now()->addYear();
            $existing->quote = $plan->books_quota * 12;
            $existing->status = 'active';
            Subscription::withoutEvents(function () use ($existing) {
                $existing->save();
            });
            $this->info("Updated subscription #{$existing->id} for {$user->email} (plan #{$plan->id} {$plan->name}).");

            return 0;
        }

        if ($existing !== null && $this->option('force')) {
            Subscription::withoutEvents(function () use ($existing) {
                $existing->delete();
            });
        }

        $subscription = new Subscription;
        $subscription->user_id = $user->id;
        $subscription->plan_id = $plan->id;
        $subscription->start = now();
        $subscription->end = now()->addYear();
        $subscription->quote = $plan->books_quota * 12;
        $subscription->status = 'active';
        $subscription->transaction_amount = 0;
        $subscription->transaction_date = now();

        Subscription::withoutEvents(function () use ($subscription) {
            $subscription->save();
        });

        $this->info("Created active subscription #{$subscription->id} for {$user->email} (plan #{$plan->id} {$plan->name}).");

        return 0;
    }

    private function resolveSuperAdminUser(): ?User
    {
        $email = trim((string) config('superadmin.email', ''));
        if ($email !== '') {
            $user = User::query()->whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
            if ($user !== null) {
                return $user;
            }
        }

        $id = (int) config('superadmin.id', 1);

        return User::query()->find($id);
    }
}
