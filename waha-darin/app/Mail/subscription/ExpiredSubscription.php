<?php

namespace App\Mail\subscription;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExpiredSubscription extends Mailable
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
        return $this->subject('Subscription Expired')
            ->view('emails.subscription-expired');
    }
}

