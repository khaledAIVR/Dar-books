<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire {--dry-run : Do not update anything, just report}';

    protected $description = 'Mark ended active subscriptions as expired and notify users.';

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $now = now();

        $query = Subscription::query()
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->whereNotNull('end')
            ->where('end', '<', $now)
            ->orderBy('id');

        $total = (clone $query)->count();
        $this->info("Found {$total} active subscriptions past end ({$now}).");

        if ($total === 0) {
            return 0;
        }

        if ($dryRun) {
            $this->warn('Dry-run enabled. No updates will be performed.');
            return 0;
        }

        $updated = 0;

        $query->chunkById(200, function ($subs) use (&$updated) {
            foreach ($subs as $sub) {
                $sub->status = 'expired';
                $sub->save(); // Observer will send "expired" email.
                $updated++;
            }
        });

        $this->info("Expired {$updated} subscriptions.");
        return 0;
    }
}

