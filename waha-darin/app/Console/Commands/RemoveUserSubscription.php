<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;

class RemoveUserSubscription extends Command
{
    protected $signature = 'subscription:remove-user {--email= : User email to remove subscription for}';

    protected $description = 'Remove subscription for a user by email so they can subscribe again.';

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

        $subscription = $user->subscription;

        if (! $subscription) {
            $this->warn("User {$email} has no subscription.");
            return 0;
        }

        $subscription->delete();
        $this->info("Removed subscription for user id {$user->id} ({$user->email}). They can subscribe again.");
        return 0;
    }
}
