<?php

namespace App\Console\Commands;

use App\Models\BorrowOrder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateBorrowReturnStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'borrow:update-return-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Persist WaitingReturnShipment/ReturnedBack statuses based on due date and return shipment number.';

    public function handle()
    {
        $today = Carbon::today()->toDateString();

        // Normalize legacy "auto-returned" records:
        // previously, entering a return shipment number could set status=ReturnedBack automatically.
        // With the new rule, only admin confirms the return (return_confirmed_at).
        // If it's ReturnedBack but NOT confirmed, revert it back to Delivered/WaitingReturnShipment based on due date.
        $revertedWaiting = BorrowOrder::where('status', 'ReturnedBack')
            ->whereNull('return_confirmed_at')
            ->whereDate('end_date', '<', $today)
            ->update(['status' => 'WaitingReturnShipment']);

        $revertedDelivered = BorrowOrder::where('status', 'ReturnedBack')
            ->whereNull('return_confirmed_at')
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->update(['status' => 'Delivered']);

        // If delivered and past due, status becomes WaitingReturnShipment
        // (even if the user entered a return shipment number; admin must confirm actual return).
        $waitingCount = BorrowOrder::where('status', 'Delivered')
            ->whereDate('end_date', '<', $today)
            ->update(['status' => 'WaitingReturnShipment']);

        $this->info("Reverted legacy ReturnedBack: Delivered={$revertedDelivered}, WaitingReturnShipment={$revertedWaiting}. Updated WaitingReturnShipment={$waitingCount}");

        return 0;
    }
}

