<?php

namespace App\Mail\Borrow;

use App\Models\BorrowOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BeforeEndBorrow extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @var BorrowOrder
     */
    public $borrowOrder;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(BorrowOrder $borrowOrder)
    {
        //
        $this->borrowOrder = $borrowOrder;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("Borrow End Reminder")
            ->view('emails.start-borrow');
    }
}
