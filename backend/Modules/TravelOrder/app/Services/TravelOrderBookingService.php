<?php

namespace Modules\TravelOrder\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Mail, Storage, Validator};
use Illuminate\Validation\ValidationException;
use Modules\TravelOrder\Emails\TravelOrderBookingCompletedMail;
use Modules\TravelOrder\Models\{TravelOrder, TravelOrderBooking, TravelOrderBookingCancellation, TravelOrderEmail};
use Modules\UserManagement\Models\User;

class TravelOrderBookingService
{
    public function __construct(private TravelOrderApprovalService $approvalService) {}

    public function completeGmoProcessing(TravelOrder $travelOrder, User $user, array $data = [], ?UploadedFile $eTicket = null, ?UploadedFile $hotelReservation = null): TravelOrder
    {
        abort_unless(in_array($travelOrder->status, ['awaiting_gmo_processing', 'awaiting_gmo_booking_document_issuance'], true), 422, 'The Travel Order is not awaiting GMO processing.');

        $isDirectPayment = $travelOrder->status === 'awaiting_gmo_processing';
        $ticketBooked = $travelOrder->ticket_arrangement === 'Booked by Company';
        $accommodationBooked = $travelOrder->accommodation_arrangement === 'Booked by Company';

        $bookingData = array_merge([
            'departure_date' => $data['departure_date'] ?? null, 'departure_time' => $data['departure_time'] ?? null,
            'return_date' => $data['return_date'] ?? null, 'return_time' => $data['return_time'] ?? null,
            'ticket_currency' => $data['ticket_currency'] ?? null, 'ticket_amount' => $data['ticket_amount'] ?? null,
            'accommodation_currency' => $data['accommodation_currency'] ?? null, 'accommodation_amount' => $data['accommodation_amount'] ?? null,
        ], $data['booking'] ?? []);

        $emailData = $data['email'] ?? [];

        if (!$isDirectPayment) {
            abort_if(!$ticketBooked && !$accommodationBooked, 422, 'There are no booking components handled by GMO.');

            $errors = [];

            if ($ticketBooked) {
                if (!$eTicket && !$travelOrder->booking?->e_ticket_path) {
                    $errors['e_ticket'] = ['The final E-Ticket is required.'];
                }

                if (!isset($bookingData['ticket_amount']) || $bookingData['ticket_amount'] === '') {
                    $errors['ticket_amount'] = ['The ticket amount is required.'];
                }
            }

            if ($accommodationBooked) {
                if (!$hotelReservation && !$travelOrder->booking?->hotel_reservation_path) {
                    $errors['hotel_reservation'] = ['The final Hotel Reservation is required.'];
                }

                if (!isset($bookingData['accommodation_amount']) || $bookingData['accommodation_amount'] === '') {
                    $errors['accommodation_amount'] = ['The accommodation amount is required.'];
                }
            }

            if ($errors) {
                throw ValidationException::withMessages($errors);
            }

            if (empty($emailData['to']) || empty($emailData['subject']) || empty($emailData['message'])) {
                throw ValidationException::withMessages(['email' => ['Recipient, subject, and message are required.']]);
            }
        }

        $newFiles = [];

        try {
            $order = DB::transaction(function () use ($travelOrder, $user, $isDirectPayment, $ticketBooked, $accommodationBooked, $bookingData, $eTicket, $hotelReservation, &$newFiles) {
                if (!$isDirectPayment) {
                    $booking = $travelOrder->booking ?? new TravelOrderBooking(['travel_order_id' => $travelOrder->id]);

                    foreach (['departure_date', 'departure_time', 'return_date', 'return_time'] as $field) {
                        if (array_key_exists($field, $bookingData)) {
                            $booking->{$field} = $bookingData[$field] ?: null;
                        }
                    }

                    if ($ticketBooked) {
                        $booking->ticket_currency = $bookingData['ticket_currency'] ?? null;
                        $booking->ticket_amount = $bookingData['ticket_amount'] ?? null;

                        if ($eTicket) {
                            $path = $eTicket->store('travel-order-bookings/e-tickets', 'public');

                            if (!$path) {
                                throw new \RuntimeException('Failed to store the E-Ticket.');
                            }

                            $newFiles[] = $path;
                            $booking->e_ticket_path = $path;
                        }
                    }

                    if ($accommodationBooked) {
                        $booking->accommodation_currency = $bookingData['accommodation_currency'] ?? null;
                        $booking->accommodation_amount = $bookingData['accommodation_amount'] ?? null;

                        if ($hotelReservation) {
                            $path = $hotelReservation->store('travel-order-bookings/hotel-reservations', 'public');

                            if (!$path) {
                                throw new \RuntimeException('Failed to store the hotel reservation.');
                            }

                            $newFiles[] = $path;
                            $booking->hotel_reservation_path = $path;
                        }
                    }

                    $booking->save();
                }

                $travelOrder->status = 'processed_by_gmo';
                $travelOrder->save();

                return $travelOrder->fresh(['user', 'department', 'booking']);
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
                'travel_order_id' => $order->id, 'sent_by' => $user->id, 'to' => json_encode($to), 'cc' => $cc ? json_encode($cc) : null,
                'subject' => $emailData['subject'], 'message' => $emailData['message'], 'status' => 'pending',
            ]);

            try {
                $mail = new TravelOrderBookingCompletedMail($order);

                $mail->subject($emailData['subject']);
                $mail->with(['emailMessage' => $emailData['message']]);

                $mailer = Mail::to($to);

                if ($cc) {
                    $mailer->cc($cc);
                }

                $mailer->send($mail);

                $emailLog->update(['status' => 'sent', 'sent_at' => now(), 'error_message' => null]);

                $this->approvalService->logGmoActivity($order, $user, 'booking_confirmation_email_sent', 'Booking Confirmation Email Sent');
            } catch (\Throwable $exception) {
                report($exception);

                $emailLog->update(['status' => 'failed', 'error_message' => $exception->getMessage(), 'sent_at' => null]);

                $this->approvalService->logGmoActivity($order, $user, 'booking_confirmation_email_failed', 'Booking Confirmation Email Failed');
            }
        }

        $this->approvalService->logGmoActivity($order, $user, 'gmo_processing_completed', 'GMO Processing Completed');

        return $order;
    }

    public function updateSchedule(TravelOrder $travelOrder, User $user, array $data): TravelOrder
    {
        abort_unless($travelOrder->status === 'processed_by_gmo', 422, 'Schedule can only be adjusted after GMO processing.');

        $validated = Validator::make($data, [
            'departure_date' => ['required', 'date'], 'departure_time' => ['required', 'date_format:H:i'],
            'return_date' => ['nullable', 'date', 'after_or_equal:departure_date'], 'return_time' => ['nullable', 'date_format:H:i'],
        ])->validate();

        DB::transaction(function () use ($travelOrder, $validated) {
            $fields = ['departure_date', 'departure_time', 'return_date', 'return_time'];

            $booking = $travelOrder->booking ?? new TravelOrderBooking(['travel_order_id' => $travelOrder->id]);

            foreach ($fields as $field) {
                $value = $validated[$field] ?? null;
                $booking->{$field} = $value;
                $travelOrder->{$field} = $value;
            }

            $booking->save();
            $travelOrder->save();
        });

        $order = $travelOrder->fresh(['user', 'department', 'booking']);

        $this->approvalService->logGmoActivity($order, $user, 'booking_schedule_updated', 'Booking Schedule Updated');

        return $order;
    }

    public function updateBookingItems(TravelOrder $travelOrder, User $user, array $data, ?UploadedFile $eTicket = null, ?UploadedFile $hotelReservation = null): TravelOrder
    {
        abort_unless($travelOrder->status === 'processed_by_gmo', 422, 'Booking items can only be updated after GMO processing.');

        $validated = Validator::make($data, [
            'ticket_currency' => ['nullable', 'string', 'max:10'], 'ticket_amount' => ['nullable', 'numeric', 'min:0'],
            'accommodation_currency' => ['nullable', 'string', 'max:10'], 'accommodation_amount' => ['nullable', 'numeric', 'min:0'],
        ])->validate();

        $newFiles = [];

        try {
            DB::transaction(function () use ($travelOrder, $validated, $eTicket, $hotelReservation, &$newFiles) {
                $booking = $travelOrder->booking ?? new TravelOrderBooking(['travel_order_id' => $travelOrder->id]);

                foreach (['ticket_currency', 'ticket_amount', 'accommodation_currency', 'accommodation_amount'] as $field) {
                    if (array_key_exists($field, $validated)) {
                        $booking->{$field} = $validated[$field] ?? null;
                    }
                }

                if ($eTicket) {
                    $path = $eTicket->store('travel-order-bookings/e-tickets', 'public');

                    if (!$path) {
                        throw new \RuntimeException('Failed to store the E-Ticket.');
                    }

                    $newFiles[] = $path;
                    $booking->e_ticket_path = $path;
                    $booking->ticket_cancelled = false;
                }

                if ($hotelReservation) {
                    $path = $hotelReservation->store('travel-order-bookings/hotel-reservations', 'public');

                    if (!$path) {
                        throw new \RuntimeException('Failed to store the hotel reservation.');
                    }

                    $newFiles[] = $path;
                    $booking->hotel_reservation_path = $path;
                    $booking->accommodation_cancelled = false;
                }

                $booking->save();
            });
        } catch (\Throwable $exception) {
            foreach ($newFiles as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        $order = $travelOrder->fresh(['user', 'department', 'booking']);

        $this->approvalService->logGmoActivity($order, $user, 'booking_items_updated', 'Booking Items Updated');

        return $order;
    }

    public function cancelBookingItem(TravelOrder $travelOrder, User $user, string $itemType, array $data, ?UploadedFile $confirmationFile = null): TravelOrderBookingCancellation
    {
        abort_unless(in_array($travelOrder->status, ['processed_by_gmo', 'cancelled'], true), 422, 'Booking items can only be cancelled after GMO processing.');
        abort_unless(in_array($itemType, ['ticket', 'accommodation'], true), 422, 'Invalid booking item type.');

        $orderAlreadyCancelled = $travelOrder->status === 'cancelled';

        $booking = $travelOrder->booking;

        abort_if(!$booking, 422, 'No booking record exists for this Travel Order.');

        $isTicket = $itemType === 'ticket';

        $alreadyCancelled = $isTicket ? $booking->ticket_cancelled : $booking->accommodation_cancelled;

        abort_if($alreadyCancelled, 422, 'This booking item has already been cancelled.');

        $amount = $isTicket ? $booking->ticket_amount : $booking->accommodation_amount;
        $currency = $isTicket ? $booking->ticket_currency : $booking->accommodation_currency;
        $attachmentPath = $isTicket ? $booking->e_ticket_path : $booking->hotel_reservation_path;

        abort_if(!$attachmentPath, 422, 'The selected booking item has no booking attachment.');

        $validated = Validator::make($data, [
            'reason' => ['required', 'string', 'max:2000'],
            'refund_status' => ['required', 'in:Fully Refund,Partially Refunded,Pending Refund,No Refund'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'], 'non_refundable_amount' => ['nullable', 'numeric', 'min:0'], 'currency' => ['nullable', 'string', 'max:10'],
        ])->validate();

        $refundAmount = (float) ($validated['refund_amount'] ?? 0);
        $nonRefundableAmount = (float) ($validated['non_refundable_amount'] ?? 0);
        $originalAmount = (float) ($amount ?? 0);

        if ($validated['refund_status'] === 'Fully Refund') {
            $refundAmount = $originalAmount;
            $nonRefundableAmount = 0;
        } elseif ($validated['refund_status'] === 'No Refund') {
            $refundAmount = 0;
            $nonRefundableAmount = $originalAmount;
        } elseif ($validated['refund_status'] === 'Pending Refund') {
            $refundAmount = 0;
            $nonRefundableAmount = 0;
        }

        abort_if($refundAmount + $nonRefundableAmount > $originalAmount, 422, 'Refund and non-refundable amounts cannot exceed the original amount.');

        $confirmationPath = null;

        try {
            if ($confirmationFile) {
                $confirmationPath = $confirmationFile->store('travel-order-bookings/cancellations', 'public');

                if (!$confirmationPath) {
                    throw new \RuntimeException('Failed to store the cancellation confirmation.');
                }
            }

            $cancellation = DB::transaction(function () use ($travelOrder, $booking, $user, $itemType, $isTicket, $validated, $currency, $originalAmount, $refundAmount, $nonRefundableAmount, $confirmationPath) {
                $cancellation = TravelOrderBookingCancellation::create([
                    'travel_order_id' => $travelOrder->id, 'booking_id' => $booking->id, 'item_type' => $itemType,
                    'reason' => $validated['reason'], 'refund_status' => $validated['refund_status'], 'currency' => $validated['currency'] ?? $currency,
                    'original_amount' => $originalAmount, 'refund_amount' => $refundAmount, 'non_refundable_amount' => $nonRefundableAmount,
                    'confirmation_file' => $confirmationPath, 'processed_by' => $user->id,
                ]);

                if ($isTicket) {
                    $booking->ticket_cancelled = true;
                } else {
                    $booking->accommodation_cancelled = true;
                }

                $booking->save();

                if ($travelOrder->status !== 'cancelled') {
                    $travelOrder->update(['status' => 'cancelled']);
                }

                return $cancellation;
            });
        } catch (\Throwable $exception) {
            if ($confirmationPath) {
                Storage::disk('public')->delete($confirmationPath);
            }

            throw $exception;
        }

        $this->approvalService->logGmoActivity($travelOrder, $user, $itemType . '_cancellation_recorded', ucfirst($itemType) . ' Cancellation Recorded');

        if (!$orderAlreadyCancelled) {
            $this->approvalService->logGmoActivity($travelOrder, $user, 'cancelled', 'Travel Order cancelled by Admin-GMO', ['role' => 'Admin-GMO', 'remark' => $validated['reason']]);
        }

        return $cancellation;
    }

    public function updateCancellationRefund(TravelOrder $travelOrder, User $user, string $itemType, array $data, ?UploadedFile $confirmationFile = null): TravelOrderBookingCancellation
    {
        abort_unless(in_array($itemType, ['ticket', 'accommodation'], true), 422, 'Invalid booking item type.');

        $cancellation = TravelOrderBookingCancellation::where('travel_order_id', $travelOrder->id)->where('item_type', $itemType)->firstOrFail();

        $validated = Validator::make(array_merge($data, ['confirmation_file' => $confirmationFile]), [
            'reason' => ['required', 'string', 'max:2000'],
            'refund_status' => ['required', 'in:Fully Refund,Partially Refunded,Pending Refund,No Refund'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'], 'non_refundable_amount' => ['nullable', 'numeric', 'min:0'],
            'confirmation_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ])->validate();

        $originalAmount = (float) $cancellation->original_amount;
        $refundAmount = (float) ($validated['refund_amount'] ?? 0);
        $nonRefundableAmount = (float) ($validated['non_refundable_amount'] ?? 0);

        if ($validated['refund_status'] === 'Fully Refund') {
            $refundAmount = $originalAmount;
            $nonRefundableAmount = 0;
        } elseif ($validated['refund_status'] === 'No Refund') {
            $refundAmount = 0;
            $nonRefundableAmount = $originalAmount;
        } elseif ($validated['refund_status'] === 'Pending Refund') {
            $refundAmount = 0;
            $nonRefundableAmount = 0;
        }

        abort_if($refundAmount + $nonRefundableAmount > $originalAmount, 422, 'Refund and non-refundable amounts cannot exceed the original amount.');

        $oldPath = $cancellation->confirmation_file;
        $newPath = null;

        try {
            if ($confirmationFile) {
                $newPath = $confirmationFile->store('travel-order-bookings/cancellations', 'public');

                if (!$newPath) {
                    throw new \RuntimeException('Failed to store the cancellation confirmation.');
                }
            }

            DB::transaction(function () use ($cancellation, $validated, $refundAmount, $nonRefundableAmount, $newPath, $user) {
                $cancellation->reason = $validated['reason'];
                $cancellation->refund_status = $validated['refund_status'];
                $cancellation->refund_amount = $refundAmount;
                $cancellation->non_refundable_amount = $nonRefundableAmount;
                $cancellation->processed_by = $user->id;

                if ($newPath) {
                    $cancellation->confirmation_file = $newPath;
                }

                $cancellation->save();
            });
        } catch (\Throwable $exception) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }

            throw $exception;
        }

        if ($newPath && $oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $this->approvalService->logGmoActivity($travelOrder, $user, $itemType . '_refund_updated', ucfirst($itemType) . ' Refund Updated');

        return $cancellation->fresh();
    }

    public function resendBookingEmail(TravelOrder $travelOrder, User $user, array $data): TravelOrderEmail
    {
        abort_unless($travelOrder->status === 'processed_by_gmo', 422, 'Booking confirmation email can only be resent after GMO processing.');

        $validated = Validator::make($data, [
            'to' => ['required'], 'cc' => ['nullable'], 'subject' => ['required', 'string', 'max:255'], 'message' => ['required', 'string'],
        ])->validate();

        $normalizeRecipients = static function ($recipients): array {
            $values = is_array($recipients) ? $recipients : explode(',', (string) $recipients);

            return array_values(array_unique(array_filter(array_map('trim', $values), static fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)));
        };

        $to = $normalizeRecipients($validated['to']);
        $cc = $normalizeRecipients($validated['cc'] ?? []);

        abort_if(!$to, 422, 'At least one valid recipient email is required.');

        $emailLog = TravelOrderEmail::create([
            'travel_order_id' => $travelOrder->id, 'sent_by' => $user->id, 'to' => json_encode($to), 'cc' => $cc ? json_encode($cc) : null,
            'subject' => $validated['subject'], 'message' => $validated['message'], 'status' => 'pending',
        ]);

        try {
            $mail = new TravelOrderBookingCompletedMail($travelOrder);

            $mail->subject($validated['subject']);
            $mail->with(['emailMessage' => $validated['message']]);

            $mailer = Mail::to($to);

            if ($cc) {
                $mailer->cc($cc);
            }

            $mailer->send($mail);

            $emailLog->update(['status' => 'sent', 'sent_at' => now(), 'error_message' => null]);

            $this->approvalService->logGmoActivity($travelOrder, $user, 'booking_confirmation_email_resent', 'Booking Confirmation Email Resent');
        } catch (\Throwable $exception) {
            report($exception);

            $emailLog->update(['status' => 'failed', 'error_message' => $exception->getMessage(), 'sent_at' => null]);

            throw $exception;
        }

        return $emailLog;
    }
}