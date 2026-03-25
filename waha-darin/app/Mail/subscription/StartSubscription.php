<?php

namespace App\Mail\subscription;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StartSubscription extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var Subscription
     */
    public $subscription;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Subscription $subscription)
    {
        //
        $this->subscription = $subscription;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("Start Subscription")
            ->view('emails.start-subscription');
    }
}
