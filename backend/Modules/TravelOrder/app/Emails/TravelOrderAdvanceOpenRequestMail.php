<?php

namespace Modules\TravelOrder\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\TravelOrder\Models\TravelOrder;

class TravelOrderAdvanceOpenRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TravelOrder $order,
        public string $remarks
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject("Travel Order {$this->order->order_number} requesting Travel Advance to be opened")
            ->view('travelorder::emails.advance_open_request');
    }
}
