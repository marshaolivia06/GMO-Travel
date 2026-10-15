<?php

namespace Modules\TravelOrder\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\TravelOrder\Models\TravelOrder;

class TravelOrderBookingSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TravelOrder $order
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject("Travel Order {$this->order->order_number} booking information submitted")
            ->view('travelorder::emails.booking_submitted');
    }
}
