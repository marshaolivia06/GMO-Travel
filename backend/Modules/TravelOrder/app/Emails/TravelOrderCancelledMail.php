<?php

namespace Modules\TravelOrder\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\TravelOrder\Models\TravelOrder;

class TravelOrderCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TravelOrder $order,
        public string $actorName,
        public string $roleName,
        public string $remark
    ) {}

    public function build(): self
    {
        return $this
            ->subject("Travel Order {$this->order->order_number} cancelled")
            ->view('travelorder::emails.travel_order_cancelled');
    }
}