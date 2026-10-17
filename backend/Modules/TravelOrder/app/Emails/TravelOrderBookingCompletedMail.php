<?php

namespace Modules\TravelOrder\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\TravelOrder\Models\TravelOrder;

class TravelOrderBookingCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TravelOrder $order
    ) {}

    public function build(): self
    {
        $ticketBooked = $this->order->ticket_arrangement === 'Booked by Company';
        $accommodationBooked = $this->order->accommodation_arrangement === 'Booked by Company';

        $subject = match (true) {
            $ticketBooked && $accommodationBooked => 'Flight Ticket and Hotel Reservation Confirmed',
            $ticketBooked => 'Flight Ticket Issued',
            $accommodationBooked => 'Hotel Reservation Confirmed',
            default => 'Travel Booking Confirmation',
        };

        return $this
            ->subject("Travel Order {$this->order->order_number} - {$subject}")
            ->view('travelorder::emails.booking_completed');
    }
}
