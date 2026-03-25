<?php

namespace App\Console\Commands;

use App\Mail\Borrow\ReturnReminder;
use App\Models\BorrowOrder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendBorrowReturnReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'borrow:return-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send return reminder emails for borrows ending tomorrow.';

    public function handle()
    {
        $tomorrow = Carbon::tomorrow()->toDateString();

        $orders = BorrowOrder::where('status', 'Delivered')
            ->whereNull('return_shipment_number')
            ->whereDate('end_date', $tomorrow)
            ->with('user')
            ->get();

        $sent = 0;
        foreach ($orders as $order) {
            if (!$order->user) {
                continue;
            }
            Mail::to($order->user)->send(new ReturnReminder($order));
            $sent++;
        }

        $this->info("Sent {$sent} reminder(s).");

        return 0;
    }
}

