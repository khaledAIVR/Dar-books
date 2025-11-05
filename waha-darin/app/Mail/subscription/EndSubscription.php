<?php

namespace App\Mail\subscription;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EndSubscription extends Mailable implements ShouldQueue
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
        return $this->subject("End Subscription")
            ->view('emails.start-subscription');
    }
}
