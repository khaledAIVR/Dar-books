<?php

namespace App\Observers;

use App\Mail\Borrow\BeforeEndBorrow;
use App\Mail\Borrow\EndBorrow;
use App\Mail\Borrow\StartBorrow;
use App\Models\BorrowOrder;
use Illuminate\Support\Facades\Mail;

class BorrowOrderObserver
{
    /**
     * Handle the borrow order "created" event.
     *
     * @param  \App\Models\BorrowOrder  $borrowOrder
     * @return void
     */
    public function created(BorrowOrder $borrowOrder)
    {
        //
    }

    /**
     * Handle the borrow order "updated" event.
     *
     * @param  \App\Models\BorrowOrder  $borrowOrder
     * @return void
     */
    public function updated(BorrowOrder $borrowOrder)
    {
        //
        if ($borrowOrder->isDirty('status') && strtolower($borrowOrder->status) == 'delivered'){
            $start      = new StartBorrow($borrowOrder);
            $end       = new EndBorrow($borrowOrder);
            $beforeEnd = new BeforeEndBorrow($borrowOrder);
            Mail::to($borrowOrder->user)->send($start);
            Mail::to($borrowOrder->user)->later($borrowOrder->end_date, $end);
            Mail::to($borrowOrder->user)->later($borrowOrder->end_date->subWeeks(2), $beforeEnd);
        }

    }

    /**
     * Handle the borrow order "deleted" event.
     *
     * @param  \App\Models\BorrowOrder  $borrowOrder
     * @return void
     */
    public function deleted(BorrowOrder $borrowOrder)
    {
        //
    }

    /**
     * Handle the borrow order "restored" event.
     *
     * @param  \App\Models\BorrowOrder  $borrowOrder
     * @return void
     */
    public function restored(BorrowOrder $borrowOrder)
    {
        //
    }

    /**
     * Handle the borrow order "force deleted" event.
     *
     * @param  \App\Models\BorrowOrder  $borrowOrder
     * @return void
     */
    public function forceDeleted(BorrowOrder $borrowOrder)
    {
        //
    }
}
