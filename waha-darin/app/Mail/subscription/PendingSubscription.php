<?php

namespace App\Mail\subscription;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PendingSubscription extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var Subscription
     */
    public $subscription;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    public function build()
    {
        return $this->subject('Subscription Pending')
            ->view('emails.subscription-pending');
    }
}

