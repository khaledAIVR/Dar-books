<?php

namespace App\Mail\Borrow;

use App\Models\BorrowOrder;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderDelivered extends Mailable
{
    use SerializesModels;

    /**
     * @var BorrowOrder
     */
    public $borrowOrder;

    public function __construct(BorrowOrder $borrowOrder)
    {
        $this->borrowOrder = $borrowOrder;
    }

    public function build()
    {
        return $this->subject('Borrow order delivered')
            ->markdown('emails.borrow-delivered');
    }
}

