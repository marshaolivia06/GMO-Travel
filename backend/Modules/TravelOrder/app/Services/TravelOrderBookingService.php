<?php

namespace Modules\TravelOrder\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\TravelOrder\Models\TravelOrder;
use Modules\TravelOrder\Models\TravelOrderBooking;
use Modules\TravelOrder\Services\TravelOrderApprovalService;
use Modules\UserManagement\Models\User;
class TravelOrderBookingService
{
    public function __construct(
        private TravelOrderApprovalService $approvalService
    ) {}
    public function saveBooking(
        TravelOrder $travelOrder,
        User $user,
        array $data,
        ?UploadedFile $ticketAttachment = null,
        ?UploadedFile $accommodationAttachment = null
    ): TravelOrderBooking {
        abort_unless(
            in_array($travelOrder->status, [
                'awaiting_gmo_booking_document_issuance',
            ], true),
            422,
            'The Travel Order has not yet reached the GMO booking stage.'
        );

        $ticketBooked = $travelOrder->ticket_arrangement === 'Booked by Company';
        $accommodationBooked = $travelOrder->accommodation_arrangement === 'Booked by Company';

        abort_if(
            !$ticketBooked && !$accommodationBooked,
            422,
            'There are no booking components handled by the GMO.'
        );

        $booking = $travelOrder->booking ?? new TravelOrderBooking([
            'travel_order_id' => $travelOrder->id,
        ]);

        $fields = [
            'departure_date',
            'departure_time',
            'return_date',
            'return_time',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $booking->{$field} = $data[$field];
            }
        }

        if ($ticketBooked) {
            foreach (['ticket_currency', 'ticket_amount'] as $field) {
                if (array_key_exists($field, $data)) {
                    $booking->{$field} = $data[$field];
                }
            }

            if ($ticketAttachment) {
                $booking->ticket_attachment = $ticketAttachment->store(
                    'travel-order-bookings/tickets',
                    'public'
                );
            }
        }

        if ($accommodationBooked) {
            foreach (['accommodation_currency', 'accommodation_amount'] as $field) {
                if (array_key_exists($field, $data)) {
                    $booking->{$field} = $data[$field];
                }
            }

            if ($accommodationAttachment) {
                $booking->accommodation_attachment = $accommodationAttachment->store(
                    'travel-order-bookings/accommodations',
                    'public'
                );
            }
        }

        $booking->save();

        $this->approvalService->logGmoActivity(
            $travelOrder,
            $user,
            'gmo_booking_saved',
            'Ticket Booking Completed'
        );

return $booking;
    }
    public function saveETicket(
        TravelOrder $travelOrder,
        User $user,
        ?UploadedFile $eTicket = null,
        ?UploadedFile $hotelReservation = null
    ): TravelOrderBooking {
    abort_unless(
        $travelOrder->status === 'awaiting_gmo_booking_document_issuance',
        422,
        'The Travel Order has not yet reached the final travel document upload stage.'
    );

    $ticketBooked = $travelOrder->ticket_arrangement === 'Booked by Company';
    $accommodationBooked = $travelOrder->accommodation_arrangement === 'Booked by Company';

    abort_if(
        !$ticketBooked && !$accommodationBooked,
        422,
        'There are no travel documents handled by GMO.'
    );

    abort_if(
        $ticketBooked && !$eTicket && !$travelOrder->booking?->e_ticket_path,
        422,
        'The final E-Ticket is required.'
    );

    abort_if(
        $accommodationBooked && !$hotelReservation && !$travelOrder->booking?->hotel_reservation_path,
        422,
        'The final Hotel Reservation is required.'
    );

    $booking = $travelOrder->booking;

    abort_if(!$booking, 422, 'Booking data is not yet available.');

    if ($eTicket && $ticketBooked) {
        if ($booking->e_ticket_path) {
            Storage::disk('public')->delete($booking->e_ticket_path);
        }

        $booking->e_ticket_path = $eTicket->store(
            'travel-order-bookings/e-tickets',
            'public'
        );
    }

    if ($hotelReservation && $accommodationBooked) {
        if ($booking->hotel_reservation_path) {
            Storage::disk('public')->delete($booking->hotel_reservation_path);
        }

        $booking->hotel_reservation_path = $hotelReservation->store(
            'travel-order-bookings/hotel-reservations',
            'public'
        );
    }

$booking->save();

$travelOrder->status = 'processed_by_gmo';
$travelOrder->save();

$this->approvalService->logGmoActivity(
    $travelOrder,
    $user,
    'gmo_processing_completed',
    'GMO Processing Completed'
);

return $booking;
}
public function completeGmoProcessing(
    TravelOrder $travelOrder,
    User $user
): TravelOrder {
    abort_unless(
        $travelOrder->status === 'awaiting_gmo_processing',
        422,
        'The Travel Order is not awaiting GMO processing.'
    );

    $travelOrder->status = 'processed_by_gmo';
    $travelOrder->save();

    $this->approvalService->logGmoActivity(
        $travelOrder,
        $user,
        'gmo_processing_completed',
        'GMO Processing Completed'
    );

    return $travelOrder;
}

}