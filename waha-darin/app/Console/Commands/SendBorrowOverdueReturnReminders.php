<?php

namespace App\Console\Commands;

use App\Mail\Borrow\OverdueReturnReminder;
use App\Models\BorrowOrder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendBorrowOverdueReturnReminders extends Command
{
    protected $signature = 'borrow:overdue-return-reminders
                            {--dry-run : Do not send emails, only print counts}
                            {--limit=500 : Max number of emails to send per run}';

    protected $description = 'Send overdue return reminder emails every 3 days until the user adds a return shipment number.';

    public function handle()
    {
        $cutoff = Carbon::now()->subDays(3);
        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        $orders = BorrowOrder::where('status', 'WaitingReturnShipment')
            ->whereNull('return_shipment_number')
            ->whereHas('user')
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('overdue_return_reminder_sent_at')
                    ->orWhere('overdue_return_reminder_sent_at', '<=', $cutoff);
            })
            ->with('user')
            ->orderBy('overdue_return_reminder_sent_at')
            ->limit($limit)
            ->get();

        $eligible = $orders->count();
        $sent = 0;
        $skippedNoUser = 0;

        foreach ($orders as $order) {
            if (!$order->user) {
                $skippedNoUser++;
                continue;
            }

            if ($dryRun) {
                $this->line("DRY order#{$order->id} user={$order->user->email}");
                continue;
            }

            Mail::to($order->user)->send(new OverdueReturnReminder($order));
            $order->overdue_return_reminder_sent_at = Carbon::now();
            $order->save();
            $sent++;
        }

        $this->info("Eligible={$eligible}, Sent={$sent}, SkippedNoUser={$skippedNoUser}, DryRun=".($dryRun ? 'yes' : 'no'));

        return 0;
    }
}

