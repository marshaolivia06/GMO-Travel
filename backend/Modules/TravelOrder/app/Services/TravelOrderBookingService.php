<?php

namespace Modules\TravelOrder\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\TravelOrder\Emails\TravelOrderBookingCompletedMail;
use Modules\TravelOrder\Models\TravelOrder;
use Modules\TravelOrder\Models\TravelOrderBooking;
use Modules\TravelOrder\Services\TravelOrderApprovalService;
use Modules\UserManagement\Models\User;
use Modules\TravelOrder\Models\TravelOrderEmail;

class TravelOrderBookingService
{
    public function __construct(
        private TravelOrderApprovalService $approvalService
    ) {}

    public function completeGmoProcessing(
        TravelOrder $travelOrder,
        User $user,
        array $data = [],
        ?UploadedFile $eTicket = null,
        ?UploadedFile $hotelReservation = null
    ): TravelOrder {
        abort_unless(
            in_array($travelOrder->status, [
                'awaiting_gmo_processing',
                'awaiting_gmo_booking_document_issuance',
            ], true),
            422,
            'The Travel Order is not awaiting GMO processing.'
        );

        $isDirectPayment = $travelOrder->status === 'awaiting_gmo_processing';

        $ticketBooked = $travelOrder->ticket_arrangement === 'Booked by Company';
        $accommodationBooked =
            $travelOrder->accommodation_arrangement === 'Booked by Company';

       $bookingData = array_merge([
    'departure_date' => $data['departure_date'] ?? null,
    'departure_time' => $data['departure_time'] ?? null,
    'return_date' => $data['return_date'] ?? null,
    'return_time' => $data['return_time'] ?? null,
    'ticket_currency' => $data['ticket_currency'] ?? null,
    'ticket_amount' => $data['ticket_amount'] ?? null,
    'accommodation_currency' => $data['accommodation_currency'] ?? null,
    'accommodation_amount' => $data['accommodation_amount'] ?? null,
], $data['booking'] ?? []);

$emailData = $data['email'] ?? [];

        if (!$isDirectPayment) {
            abort_if(
                !$ticketBooked && !$accommodationBooked,
                422,
                'There are no booking components handled by GMO.'
            );

            $errors = [];

            if ($ticketBooked) {
                if (!$eTicket && !$travelOrder->booking?->e_ticket_path) {
                    $errors['e_ticket'] = [
                        'The final E-Ticket is required.',
                    ];
                }

                if (
                    !isset($bookingData['ticket_amount']) ||
                    $bookingData['ticket_amount'] === ''
                ) {
                    $errors['ticket_amount'] = [
                        'The ticket amount is required.',
                    ];
                }
            }

            if ($accommodationBooked) {
                if (
                    !$hotelReservation &&
                    !$travelOrder->booking?->hotel_reservation_path
                ) {
                    $errors['hotel_reservation'] = [
                        'The final Hotel Reservation is required.',
                    ];
                }

                if (
                    !isset($bookingData['accommodation_amount']) ||
                    $bookingData['accommodation_amount'] === ''
                ) {
                    $errors['accommodation_amount'] = [
                        'The accommodation amount is required.',
                    ];
                }
            }

            if ($errors) {
                throw ValidationException::withMessages($errors);
            }

            if (
                empty($emailData['to']) ||
                empty($emailData['subject']) ||
                empty($emailData['message'])
            ) {
                throw ValidationException::withMessages([
                    'email' => [
                        'Recipient, subject, and message are required.',
                    ],
                ]);
            }
        }

        $newFiles = [];

        try {
            $order = DB::transaction(function () use (
                $travelOrder,
                $user,
                $isDirectPayment,
                $ticketBooked,
                $accommodationBooked,
                $bookingData,
                $eTicket,
                $hotelReservation,
                &$newFiles
            ) {
                if (!$isDirectPayment) {
                    $booking = $travelOrder->booking
                        ?? new TravelOrderBooking([
                            'travel_order_id' => $travelOrder->id,
                        ]);

                    foreach ([
                        'departure_date',
                        'departure_time',
                        'return_date',
                        'return_time',
                    ] as $field) {
                        if (array_key_exists($field, $bookingData)) {
                            $booking->{$field} = $bookingData[$field] ?: null;
                        }
                    }

                    if ($ticketBooked) {
                        $booking->ticket_currency =
                            $bookingData['ticket_currency'] ?? null;

                        $booking->ticket_amount =
                            $bookingData['ticket_amount'] ?? null;

                        if ($eTicket) {
                            $path = $eTicket->store(
                                'travel-order-bookings/e-tickets',
                                'public'
                            );

                            if (!$path) {
                                throw new \RuntimeException(
                                    'Failed to store the E-Ticket.'
                                );
                            }

                            $newFiles[] = $path;
                            $booking->e_ticket_path = $path;
                        }
                    }

                    if ($accommodationBooked) {
                        $booking->accommodation_currency =
                            $bookingData['accommodation_currency'] ?? null;

                        $booking->accommodation_amount =
                            $bookingData['accommodation_amount'] ?? null;

                        if ($hotelReservation) {
                            $path = $hotelReservation->store(
                                'travel-order-bookings/hotel-reservations',
                                'public'
                            );

                            if (!$path) {
                                throw new \RuntimeException(
                                    'Failed to store the hotel reservation.'
                                );
                            }

                            $newFiles[] = $path;
                            $booking->hotel_reservation_path = $path;
                        }
                    }

                    $booking->save();
                }

                $travelOrder->status = 'processed_by_gmo';
                $travelOrder->save();

                return $travelOrder->fresh([
                    'user',
                    'department',
                    'booking',
                ]);
            });
        } catch (\Throwable $exception) {
            foreach ($newFiles as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

if (!$isDirectPayment) {
    $to = $emailData['to'] ?? [];
    $cc = $emailData['cc'] ?? [];

    $to = is_array($to) ? $to : explode(',', $to);
    $cc = is_array($cc) ? $cc : explode(',', $cc);

    $to = array_values(array_filter(array_map('trim', $to)));
    $cc = array_values(array_filter(array_map('trim', $cc)));

    $emailLog = TravelOrderEmail::create([
        'travel_order_id' => $order->id,
        'sent_by' => $user->id,
        'to' => json_encode($to),
        'cc' => $cc ? json_encode($cc) : null,
        'subject' => $emailData['subject'],
        'message' => $emailData['message'],
        'status' => 'pending',
    ]);

    try {
        $mail = new TravelOrderBookingCompletedMail($order);

        $mail->subject($emailData['subject']);
        $mail->with([
            'emailMessage' => $emailData['message'],
        ]);

        $mailer = Mail::to($to);

        if ($cc) {
            $mailer->cc($cc);
        }

        $mailer->send($mail);

        $emailLog->update([
            'status' => 'sent',
            'sent_at' => now(),
            'error_message' => null,
        ]);

        $this->approvalService->logGmoActivity(
            $order,
            $user,
            'booking_confirmation_email_sent',
            'Booking Confirmation Email Sent'
        );
    } catch (\Throwable $exception) {
        report($exception);

        $emailLog->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'sent_at' => null,
        ]);

        $this->approvalService->logGmoActivity(
            $order,
            $user,
            'booking_confirmation_email_failed',
            'Booking Confirmation Email Failed'
        );
    }
}

$this->approvalService->logGmoActivity(
    $order,
    $user,
    'gmo_processing_completed',
    'GMO Processing Completed'
);

return $order;

    }
}
