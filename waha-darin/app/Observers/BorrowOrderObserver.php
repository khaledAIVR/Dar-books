<?php

namespace App\Observers;

use App\Models\BorrowOrder;

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
        // Email notifications are handled explicitly by:
        // - Order creation (Received)
        // - Admin shipment confirmation (Shipped)
        // - Admin delivery confirmation (Delivered)
        // - Scheduled command (Return reminder)
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
