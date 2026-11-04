<?php

namespace Modules\AnnualTicket\Services;

use Modules\UserManagement\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Mail, Storage, Validator};
use Illuminate\Validation\ValidationException;
use Modules\MasterManagement\Models\Department;
use Modules\MasterManagement\Models\Section;
use Modules\AnnualTicket\Models\AnnualTicket;
use Modules\AnnualTicket\Models\AnnualTicketBooking;
use Modules\AnnualTicket\Models\AnnualTicketBookingDocument;
use Modules\AnnualTicket\Models\AnnualTicketEmail;
use Modules\AnnualTicket\Models\AnnualTicketLegCancellation;
use Modules\AnnualTicket\Models\AnnualTicketFareReference;
use Modules\AnnualTicket\Models\AnnualTicketFareReferenceAttachment;
use Modules\AnnualTicket\Repositories\AnnualTicketRepository;
use Modules\AnnualTicket\Services\AnnualTicketApprovalService;
use Modules\AnnualTicket\Emails\AnnualTicketBookingCompletedMail;
use Barryvdh\DomPDF\Facade\Pdf;

class AnnualTicketService
{
    public function __construct(
        private AnnualTicketRepository $repository,
        private AnnualTicketApprovalService $approvalService,
    ) {}

    public function listFor(User $user)
    {
        $departmentCache = [];

        return $this->repository->allVisibleFor($user)
            ->each(function (AnnualTicket $ticket) use ($user, &$departmentCache) {
                $stage = $this->approvalService->currentStage($ticket);
                $ticket->loadMissing('approvals.role');
                // Tahap finalisasi pembatalan tidak punya approval row - stage_action ditentukan dari status
                $stageAction = $stage?->action_type ?? ($ticket->status === 'awaiting_cancellation_finalization_gmo' ? 'finalization' : null);
                $ticket->setAttribute('can_process', $this->approvalService->canProcess($ticket, $user));
                $ticket->setAttribute('is_owner', $ticket->employee_id === $user->id);
                $ticket->setAttribute('can_gmo_process', $this->canGmoProcess($ticket, $user));
                $ticket->setAttribute('can_gmo_summary', $user->hasRole('Admin-GMO') && in_array($ticket->status, AnnualTicketRepository::GMO_MANAGEABLE_STATUSES, true));
                $ticket->setAttribute('stage_action', $stageAction);
                $ticket->setAttribute('stage_role', $stage?->role?->name ?? ($stageAction === 'finalization' ? 'Admin-GMO' : null));
                $ticket->setAttribute('department_name', $this->employeeDepartmentName($ticket->employee_id, $departmentCache));
            });
    }

    public function findFor(User $user, int $id): AnnualTicket
    {
        $ticket = $this->repository->findByIdFor($user, $id);
        $stage = $this->approvalService->currentStage($ticket);
        $ticket->loadMissing('approvals.role');
        // Tahap finalisasi pembatalan tidak punya approval row - stage_action ditentukan dari status
        $stageAction = $stage?->action_type ?? ($ticket->status === 'awaiting_cancellation_finalization_gmo' ? 'finalization' : null);
        $ticket->setAttribute('can_process', $this->approvalService->canProcess($ticket, $user));
        $ticket->setAttribute('is_owner', $ticket->employee_id === $user->id);
        $ticket->setAttribute('can_gmo_process', $this->canGmoProcess($ticket, $user));
        $ticket->setAttribute('can_gmo_summary', $user->hasRole('Admin-GMO') && in_array($ticket->status, AnnualTicketRepository::GMO_MANAGEABLE_STATUSES, true));
        $ticket->setAttribute('stage_action', $stageAction);
        $ticket->setAttribute('stage_role', $stage?->role?->name ?? ($stageAction === 'finalization' ? 'Admin-GMO' : null));
        $ticket->setAttribute('department_name', $this->employeeDepartmentName($ticket->employee_id));

        return $ticket;
    }

    // Sumber nama departemen sama seperti pada PDF Annual Ticket (section head / dept head / dept admin)
    private function employeeDepartmentName(int $employeeId, array &$cache = []): ?string
    {
        if (array_key_exists($employeeId, $cache))
            return $cache[$employeeId];

        $department = Section::with('department')->where('section_head_id', $employeeId)->first()?->department
            ?? Department::where('dept_head_id', $employeeId)->first()
            ?? Department::where('dept_admin_id', $employeeId)->first();

        return $cache[$employeeId] = $department?->name;
    }

    public function canGmoProcess(AnnualTicket $ticket, User $user): bool
    {
        return $user->hasRole('Admin-GMO')
            && in_array($ticket->status, ['awaiting_fare_reference_gmo', 'awaiting_ticket_processing'], true);
    }

    public function processByGmo(User $user, int $id, ?string $note = null, array $fareReferences = [], array $attachments = [], array $booking = [], ?UploadedFile $eTicket = null, array $email = []): AnnualTicket
    {
        $ticket = $this->repository->findByIdFor($user, $id);

        abort_unless($user->hasRole('Admin-GMO'), 403, 'You are not allowed to process this Annual Ticket.');
        abort_unless($ticket->status !== 'processed_by_gmo', 422, 'The Annual Ticket has already been processed.');
        abort_unless(in_array($ticket->status, AnnualTicketRepository::GMO_STATUSES, true), 422, 'The Annual Ticket is not at the GMO processing stage.');

        $bookedByCompany = $ticket->ticket_arrangement === 'Booked by Company';

        if ($bookedByCompany) {
            $errors = [];

            if (! isset($booking['amount']) || (float) $booking['amount'] <= 0) {
                $errors['booking_amount'] = ['The booking amount is required.'];
            }

            if (! $eTicket && ! $ticket->booking?->e_ticket_path) {
                $errors['e_ticket'] = ['The E-Ticket / Travel Document is required.'];
            }

            if (empty($email['to']) || empty($email['subject']) || empty($email['message'])) {
                $errors['email'] = ['Recipient, subject, and message are required.'];
            }

            if ($errors) {
                throw ValidationException::withMessages($errors);
            }
        }

        $newFiles = [];
        $newDocuments = [];

        try {
            $ticket = DB::transaction(function () use ($ticket, $user, $note, $fareReferences, $attachments, $booking, $eTicket, $bookedByCompany, &$newFiles, &$newDocuments) {
                $ticket->update(['status' => 'processed_by_gmo']);

                $this->approvalService->logActivity($ticket, $user, 'processed', 'Annual Ticket processed by GMO', ['role' => 'Admin-GMO', 'note' => $note, 'final' => true]);

                foreach ($fareReferences as $reference) {
                    AnnualTicketFareReference::updateOrCreate(
                        ['request_id' => $ticket->id, 'direction' => $reference['direction']],
                        [
                            'operator_name' => $reference['operator_name'],
                            'fare_amount' => $reference['fare_amount'],
                            'currency' => $reference['currency'] ?? 'IDR',
                            'created_by' => $user->id,
                        ],
                    );
                }

                foreach ($attachments as $file) {
                    $path = $file->store('annual-tickets/fare-references', 'public');

                    AnnualTicketFareReferenceAttachment::create([
                        'request_id' => $ticket->id,
                        'file_path' => $path,
                        'created_by' => $user->id,
                    ]);
                }

                if ($bookedByCompany) {
                    $ticketBooking = $ticket->booking ?? new AnnualTicketBooking(['request_id' => $ticket->id]);

                    $ticketBooking->amount_currency = $booking['currency'] ?? 'IDR';
                    $ticketBooking->amount = $booking['amount'];
                    $ticketBooking->created_by = $user->id;

                    if ($eTicket) {
                        $originalName = $eTicket->getClientOriginalName();
                        $path = $eTicket->store('annual-tickets/e-tickets', 'public');

                        if (! $path) {
                            throw new \RuntimeException('Failed to store the E-Ticket.');
                        }

                        $newFiles[] = $path;
                        $newDocuments[] = ['path' => $path, 'name' => $originalName];
                        $ticketBooking->e_ticket_path = $path;

                        // Dokumen pertama tercatat juga sebagai issued travel document
                        AnnualTicketBookingDocument::create([
                            'request_id' => $ticket->id,
                            'file_path' => $path,
                            'original_name' => $originalName,
                            'created_by' => $user->id,
                        ]);
                    }

                    $ticketBooking->save();
                }

                return $ticket->refresh()->load(['legs', 'fareReferences', 'fareReferenceAttachments', 'booking', 'bookingDocuments', 'legCancellations', 'emails']);
            });
        } catch (\Throwable $exception) {
            foreach ($newFiles as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        if ($bookedByCompany) {
            // E-ticket awal ikut terlampir di email konfirmasi (dengan nama asli file)
            $this->sendBookingEmail($ticket, $user, $email, $newDocuments);
        }

        return $ticket;
    }

    // Tambah dokumen issued travel document - dokumen lama tetap ada
    public function updateBookingDocuments(User $user, int $id, array $documents = []): AnnualTicket
    {
        $ticket = $this->repository->findByIdFor($user, $id);

        abort_unless($user->hasRole('Admin-GMO'), 403, 'You are not allowed to update this Annual Ticket documents.');
        abort_unless(in_array($ticket->status, AnnualTicketRepository::GMO_MANAGEABLE_STATUSES, true), 422, 'Documents can only be updated after GMO processing.');

        $newFiles = [];
        $newDocuments = [];

        try {
            DB::transaction(function () use ($ticket, $user, $documents, &$newFiles, &$newDocuments) {
                foreach ($documents as $file) {
                    $originalName = $file->getClientOriginalName();
                    $path = $file->store('annual-tickets/booking-documents', 'public');

                    if (! $path) {
                        throw new \RuntimeException('Failed to store the document.');
                    }

                    $newFiles[] = $path;
                    $newDocuments[] = ['path' => $path, 'name' => $originalName];

                    AnnualTicketBookingDocument::create([
                        'request_id' => $ticket->id,
                        'file_path' => $path,
                        'original_name' => $originalName,
                        'created_by' => $user->id,
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            foreach ($newFiles as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        $ticket = $ticket->refresh()->load(['legs', 'employee.eligibleDestination', 'fareReferences', 'fareReferenceAttachments', 'booking', 'bookingDocuments', 'legCancellations', 'emails']);

        $this->approvalService->logActivity($ticket, $user, 'booking_documents_updated', 'Issued Travel Document Updated', ['role' => 'Admin-GMO', 'count' => count($newFiles), 'documents' => $this->documentPayload($newDocuments)]);

        // Dokumen baru otomatis dikirim ke email karyawan sebagai lampiran - sesuai prototype
        $employeeEmail = $ticket->employee?->email;

        if ($employeeEmail) {
            $this->sendBookingEmail($ticket, $user, [
                'to' => [$employeeEmail],
                'subject' => "Annual Ticket {$ticket->document_number} - New Travel Document Issued",
                'message' => 'The following travel document(s) have been issued for your Annual Ticket and are attached to this email.',
            ], $newDocuments);
        }

        return $ticket;
    }

    // Batalkan tiket per leg (Traveler + Direction) beserta refund-nya - pola sama seperti TravelOrderBookingService
    public function cancelLeg(User $user, int $id, int $legId, array $data, ?UploadedFile $confirmationFile = null): AnnualTicketLegCancellation
    {
        $ticket = $this->repository->findByIdFor($user, $id);

        abort_unless($user->hasRole('Admin-GMO'), 403, 'You are not allowed to cancel this ticket.');
        abort_unless(in_array($ticket->status, AnnualTicketRepository::GMO_MANAGEABLE_STATUSES, true), 422, 'Tickets can only be cancelled after GMO processing.');

        $leg = $ticket->legs()->whereKey($legId)->first();

        abort_unless($leg, 422, 'The selected travel leg was not found.');
        abort_if($leg->cancellation()->exists(), 422, 'This ticket has already been cancelled.');

        $validated = Validator::make($data, [
            'reason' => ['required', 'string', 'max:2000'],
            'refund_status' => ['required', 'in:Fully Refund,Partially Refunded,Pending Refund,No Refund'],
            // Harga asli satuan tiket diinput GMO saat pembatalan
            'original_amount' => ['required', 'numeric', 'min:1'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'non_refundable_amount' => ['nullable', 'numeric', 'min:0'],
        ])->validate();

        $originalAmount = (float) $validated['original_amount'];
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

        $confirmationPath = null;

        try {
            if ($confirmationFile) {
                $confirmationPath = $confirmationFile->store('annual-tickets/cancellations', 'public');

                if (! $confirmationPath) {
                    throw new \RuntimeException('Failed to store the cancellation confirmation.');
                }
            }

            $cancellation = DB::transaction(function () use ($ticket, $leg, $user, $validated, $originalAmount, $refundAmount, $nonRefundableAmount, $confirmationPath) {
                $cancellation = AnnualTicketLegCancellation::create([
                    'request_id' => $ticket->id,
                    'leg_id' => $leg->id,
                    'reason' => $validated['reason'],
                    'refund_status' => $validated['refund_status'],
                    'currency' => $ticket->booking?->amount_currency ?? 'IDR',
                    'original_amount' => $originalAmount,
                    'refund_amount' => $refundAmount,
                    'non_refundable_amount' => $nonRefundableAmount,
                    'confirmation_file' => $confirmationPath,
                    'processed_by' => $user->id,
                ]);

                // Ada leg/tiket yang dibatalkan -> status tiket menjadi Partially Cancelled
                $ticket->update(['status' => 'partially_cancelled']);

                return $cancellation;
            });
        } catch (\Throwable $exception) {
            if ($confirmationPath) {
                Storage::disk('public')->delete($confirmationPath);
            }

            throw $exception;
        }

        $this->approvalService->logActivity($ticket, $user, 'ticket_cancelled', 'Ticket Cancellation & Refund Recorded', ['role' => 'Admin-GMO', 'leg_id' => $leg->id, 'refund_status' => $validated['refund_status']]);

        return $cancellation;
    }

    // Update refund untuk tiket leg yang sudah dibatalkan - pola sama seperti updateCancellationRefund Travel Order
    public function updateLegCancellationRefund(User $user, int $id, int $legId, array $data, ?UploadedFile $confirmationFile = null): AnnualTicketLegCancellation
    {
        $ticket = $this->repository->findByIdFor($user, $id);

        abort_unless($user->hasRole('Admin-GMO'), 403, 'You are not allowed to update this refund.');
        abort_unless(in_array($ticket->status, AnnualTicketRepository::GMO_MANAGEABLE_STATUSES, true), 422, 'Refunds can only be updated after GMO processing.');

        $cancellation = AnnualTicketLegCancellation::where('request_id', $ticket->id)->where('leg_id', $legId)->first();

        abort_unless($cancellation, 422, 'No cancellation record exists for this travel leg.');

        $validated = Validator::make($data, [
            'reason' => ['required', 'string', 'max:2000'],
            'refund_status' => ['required', 'in:Fully Refund,Partially Refunded,Pending Refund,No Refund'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'non_refundable_amount' => ['nullable', 'numeric', 'min:0'],
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
                $newPath = $confirmationFile->store('annual-tickets/cancellations', 'public');

                if (! $newPath) {
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

        $this->approvalService->logActivity($ticket, $user, 'leg_refund_updated', 'Ticket Refund Updated', ['role' => 'Admin-GMO', 'leg_id' => $legId, 'refund_status' => $validated['refund_status']]);

        return $cancellation->fresh();
    }

    // Kirim ulang email konfirmasi booking - pola sama seperti resendBookingEmail Travel Order
    public function resendBookingEmail(User $user, int $id, array $data): AnnualTicketEmail
    {
        $ticket = $this->repository->findByIdFor($user, $id);

        abort_unless($user->hasRole('Admin-GMO'), 403, 'You are not allowed to resend this email.');
        abort_unless(in_array($ticket->status, AnnualTicketRepository::GMO_MANAGEABLE_STATUSES, true), 422, 'The booking confirmation email can only be resent after GMO processing.');

        $validated = Validator::make($data, [
            'to' => ['required'],
            'cc' => ['nullable'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ])->validate();

        $normalizeRecipients = static function ($recipients): array {
            $values = is_array($recipients) ? $recipients : explode(',', (string) $recipients);

            return array_values(array_unique(array_filter(array_map('trim', $values), static fn ($emailAddress) => filter_var($emailAddress, FILTER_VALIDATE_EMAIL) !== false)));
        };

        $to = $normalizeRecipients($validated['to']);
        $cc = $normalizeRecipients($validated['cc'] ?? []);

        abort_if(! $to, 422, 'At least one valid recipient email is required.');

        $emailLog = AnnualTicketEmail::create([
            'annual_ticket_id' => $ticket->id, 'sent_by' => $user->id, 'to' => json_encode($to), 'cc' => $cc ? json_encode($cc) : null,
            'subject' => $validated['subject'], 'message' => $validated['message'], 'status' => 'pending',
        ]);

        try {
            // Semua issued travel document ikut terlampir saat resend (pakai nama asli file)
            $documentPaths = $ticket->bookingDocuments
                ->map(fn ($document) => ['path' => $document->file_path, 'name' => $document->original_name])
                ->all();

            $mail = new AnnualTicketBookingCompletedMail($ticket, $documentPaths);

            $mail->subject($validated['subject']);
            $mail->with(['emailMessage' => $validated['message']]);

            $mailer = Mail::to($to);

            if ($cc) {
                $mailer->cc($cc);
            }

            $mailer->send($mail);

            $emailLog->update(['status' => 'sent', 'sent_at' => now(), 'error_message' => null]);

            $this->approvalService->logActivity($ticket, $user, 'booking_confirmation_email_resent', 'Booking Confirmation Email Resent', ['role' => 'Admin-GMO', 'documents' => $this->documentPayload($documentPaths)]);
        } catch (\Throwable $exception) {
            report($exception);

            $emailLog->update(['status' => 'failed', 'error_message' => $exception->getMessage(), 'sent_at' => null]);

            $this->approvalService->logActivity($ticket, $user, 'booking_confirmation_email_failed', 'Booking Confirmation Email Failed', ['role' => 'Admin-GMO']);

            throw $exception;
        }

        return $emailLog;
    }

    // Daftar dokumen (nama + URL) yang dicatat di properti history log agar bisa dilihat filenya
    private function documentPayload(array $documents): array
    {
        return array_map(static function ($document) {
            $path = is_array($document) ? ($document['path'] ?? '') : $document;
            $name = is_array($document) ? ($document['name'] ?? null) : null;

            return ['name' => $name ?: basename($path), 'url' => Storage::disk('public')->url($path)];
        }, array_values(array_filter($documents)));
    }

    // Kirim email konfirmasi booking ke employee - pola sama seperti TravelOrderBookingService
    private function sendBookingEmail(AnnualTicket $ticket, User $user, array $email, array $documentPaths = []): void
    {
        $normalizeRecipients = static function ($recipients): array {
            $values = is_array($recipients) ? $recipients : explode(',', (string) $recipients);

            return array_values(array_unique(array_filter(array_map('trim', $values), static fn ($emailAddress) => filter_var($emailAddress, FILTER_VALIDATE_EMAIL) !== false)));
        };

        $to = $normalizeRecipients($email['to'] ?? []);
        $cc = $normalizeRecipients($email['cc'] ?? []);

        $emailLog = AnnualTicketEmail::create([
            'annual_ticket_id' => $ticket->id, 'sent_by' => $user->id, 'to' => json_encode($to), 'cc' => $cc ? json_encode($cc) : null,
            'subject' => $email['subject'], 'message' => $email['message'], 'status' => 'pending',
        ]);

        try {
            $mail = new AnnualTicketBookingCompletedMail($ticket, $documentPaths);

            $mail->subject($email['subject']);
            $mail->with(['emailMessage' => $email['message']]);

            $mailer = Mail::to($to);

            if ($cc) {
                $mailer->cc($cc);
            }

            $mailer->send($mail);

            $emailLog->update(['status' => 'sent', 'sent_at' => now(), 'error_message' => null]);

            $this->approvalService->logActivity($ticket, $user, 'booking_confirmation_email_sent', 'Booking Confirmation Email Sent', ['role' => 'Admin-GMO', 'documents' => $this->documentPayload($documentPaths)]);
        } catch (\Throwable $exception) {
            report($exception);

            $emailLog->update(['status' => 'failed', 'error_message' => $exception->getMessage(), 'sent_at' => null]);

            $this->approvalService->logActivity($ticket, $user, 'booking_confirmation_email_failed', 'Booking Confirmation Email Failed', ['role' => 'Admin-GMO']);
        }
    }

    // Kuota tiket tahunan: tahun kuota = tahun ini, atau tahun lalu jika tanggal hari ini (dd-MM)
    // masih sebelum tanggal join karyawan - periode entitlement dihitung dari anniversary join.
    public function quotaFor(User $user): array
    {
        $join = $user->date_of_join;

        if (! $join) {
            return ['quota_year' => null, 'has_quota' => false, 'date_of_join' => null];
        }

        $today = now()->startOfDay();
        $todayMd = ((int) $today->format('n') * 100) + (int) $today->format('j');
        $joinMd = ((int) $join->format('n') * 100) + (int) $join->format('j');

        $quotaYear = $todayMd < $joinMd ? (int) $today->format('Y') - 1 : (int) $today->format('Y');

        // Kuota tersedia hanya jika tidak ada pengajuan aktif/terpakai pada tahun kuota - cegah duplikasi.
        // Hanya tiket yang dibatalkan penuh (cancelled) yang membebaskan kuota kembali.
        $hasQuota = ! AnnualTicket::query()
            ->where('employee_id', $user->id)
            ->where('quota_year', $quotaYear)
            ->where('status', '!=', 'cancelled')
            ->exists();

        return [
            'quota_year' => $quotaYear,
            'has_quota' => $hasQuota,
            'date_of_join' => $join->format('Y-m-d'),
        ];
    }

    public function create(User $user, array $data, $evidence): AnnualTicket
    {
        $quota = $this->quotaFor($user);

        abort_unless($quota['has_quota'], 422, 'The annual ticket quota for this period has already been used.');
        abort_unless((int) $data['quota_year'] === $quota['quota_year'], 422, 'The selected quota year does not match your current annual ticket period.');

        $path = $evidence->store('annual-tickets/evidence', 'public');

        $ticket = $this->repository->create($user, $data, $path, $data['travelers']);

        $this->approvalService->initFlow($ticket);

        return $ticket;
    }

    public function resubmit(User $user, int $id, array $data, $evidence): AnnualTicket
    {
        $ticket = $this->repository->findByIdForEmployee($user, $id);

        abort_unless($ticket->status === 'revision_required', 422, 'Only tickets that require revision can be resubmitted.');

        $path = $evidence ? $evidence->store('annual-tickets/evidence', 'public') : null;

        $ticket = $this->repository->resubmit($ticket, $data, $path, $data['travelers']);

        $this->approvalService->initFlow($ticket);

        return $ticket;
    }

    public function check(User $user, int $id, ?string $note = null): AnnualTicket
    {
        $ticket = $this->repository->findByIdFor($user, $id);

        $this->approvalService->check($ticket, $user, $note);

        return $ticket->refresh()->load('legs');
    }

    public function approve(User $user, int $id, ?string $note = null): AnnualTicket
    {
        $ticket = $this->repository->findByIdFor($user, $id);

        $this->approvalService->approve($ticket, $user, $note);

        return $ticket->refresh()->load('legs');
    }

    public function revision(User $user, int $id, string $remark): AnnualTicket
    {
        $ticket = $this->repository->findByIdFor($user, $id);

        $this->approvalService->revision($ticket, $user, $remark);

        return $ticket->refresh()->load('legs');
    }

    public function updateSchedule(User $user, int $id, array $reschedules = []): AnnualTicket
    {
        $ticket = $this->repository->findByIdFor($user, $id);

        abort_unless($user->hasRole('Admin-GMO'), 403, 'You are not allowed to update this Annual Ticket schedule.');

        $changed = DB::transaction(function () use ($ticket, $reschedules) {
            $changed = 0;

            foreach ($reschedules as $item) {
                $leg = $ticket->legs()->whereKey($item['leg_id'])->first();

                if (! $leg)
                    continue;

                $newDate = $item['reschedule_date'] ?? null;
                $originalDate = $leg->travel_date->format('Y-m-d');

                // Tanggal sama dengan original berarti bukan reschedule - kolom original tetap menjadi acuan
                if (! $newDate || $newDate === $originalDate) {
                    if ($leg->reschedule_date)
                        $leg->update(['reschedule_date' => null]);

                    continue;
                }

                $leg->update(['reschedule_date' => $newDate]);
                $changed++;
            }

            return $changed;
        });

        $this->approvalService->logActivity($ticket, $user, 'schedule_updated', 'Travel schedule updated by GMO', ['role' => 'Admin-GMO', 'reschedules' => $changed]);

        return $ticket->refresh()->load(['legs', 'employee.eligibleDestination', 'fareReferences', 'fareReferenceAttachments', 'booking', 'bookingDocuments', 'legCancellations', 'emails']);
    }

    public function requestCancellation(User $user, int $id, string $reason): AnnualTicket
    {
        $ticket = $this->repository->findByIdForEmployee($user, $id);

        abort_unless(in_array($ticket->status, AnnualTicketRepository::CANCELLABLE_STATUSES, true), 422, 'The Annual Ticket cannot be cancelled at this stage.');

        DB::transaction(function () use ($ticket, $user, $reason) {
            // Buat rantai approval pembatalan: HR Admin -> HR Supervisor -> Admin-GMO
            $this->approvalService->initCancellationFlow($ticket);

            $this->approvalService->logActivity($ticket, $user, 'cancel_requested', 'Cancellation requested by employee', ['role' => 'Employee', 'note' => $reason]);
        });

        return $ticket->refresh()->load(['legs', 'employee.eligibleDestination', 'fareReferences', 'fareReferenceAttachments', 'booking', 'bookingDocuments', 'legCancellations', 'emails']);
    }

    // Finalisasi pembatalan oleh Admin-GMO -> status menjadi cancelled
    public function cancelFinalize(User $user, int $id, ?string $note = null): AnnualTicket
    {
        $ticket = $this->repository->findByIdFor($user, $id);

        $this->approvalService->finalizeCancellation($ticket, $user, $note);

        return $ticket->refresh()->load(['legs', 'employee.eligibleDestination', 'fareReferences', 'fareReferenceAttachments', 'booking', 'bookingDocuments', 'legCancellations', 'emails']);
    }

    // Tolak pengajuan pembatalan (HR Supervisor) -> tiket kembali ke status semula
    public function cancelReject(User $user, int $id, string $remark): AnnualTicket
    {
        $ticket = $this->repository->findByIdFor($user, $id);

        $this->approvalService->rejectCancellation($ticket, $user, $remark);

        return $ticket->refresh()->load(['legs', 'employee.eligibleDestination', 'fareReferences', 'fareReferenceAttachments', 'booking', 'bookingDocuments', 'legCancellations', 'emails']);
    }

    public function pdf(int $id, User $user)
    {
        $ticket = $this->repository->findByIdFor($user, $id);

        $submittedLog = \Spatie\Activitylog\Models\Activity::with('causer')
            ->where('log_name', 'annual-ticket')
            ->where('subject_type', $ticket->getMorphClass())
            ->where('subject_id', $ticket->id)
            ->where('event', 'submitted')
            ->first();

        $approvals = $this->approvalService->latestRoundApprovals($ticket);

        // Baris approval alur pembatalan tidak pernah ditampilkan di PDF - catatannya cukup di history log.
        // Saat cancellation berjalan -> tampilkan Requester + rantai pembatalan TERBARIS saja;
        // setelah selesai (rejected/finalized) -> kembali ke rantai alur normal seperti sebelumnya.
        $hasCancellationRows = $ticket->approvals()->where('flow_type', 'cancellation')->exists()
            || $ticket->approvals()->whereNull('flow_type')->whereNull('authority_matrix_step_id')->whereNotNull('action_type')->exists();

        if ($hasCancellationRows) {
            $isCancellationRow = fn ($approval) => $approval->flow_type === 'cancellation'
                || (is_null($approval->flow_type) && is_null($approval->authority_matrix_step_id) && ! is_null($approval->action_type));

            if (str_starts_with($ticket->status, 'awaiting_cancellation_')) {
                // Batas rantai terbaris: jalan mundur dari sequence terakhir sampai baris alur normal
                $rows = $approvals->values();
                $latestChainStart = null;

                for ($i = $rows->count() - 1; $i >= 0; $i--) {
                    if (! $isCancellationRow($rows[$i])) {
                        break;
                    }

                    $latestChainStart = $rows[$i]->sequence;
                }

                $approvals = $approvals->filter(
                    fn ($approval) => $approval->status === 'submitted'
                        || ($latestChainStart !== null && $approval->sequence >= $latestChainStart && $isCancellationRow($approval) && $approval->action_type !== 'finalization'),
                );
            } else {
                $approvals = $approvals->filter(fn ($approval) => ! $isCancellationRow($approval));
            }
        }

        $approvals = $approvals
            ->map(fn ($approval) => (object) [
                'sequence' => $approval->sequence,
                'role' => $approval->role_name,
                'name' => $approval->name,
                'acted_at' => $approval->acted_at,
                'status' => $approval->status,
                'action_type' => $approval->action_type,
            ]);

        if ($submittedLog && $ticket->approvals()->doesntExist()) {
            $approvals->prepend((object) [
                'sequence' => 0,
                'role' => $submittedLog->properties->get('role'),
                'name' => $submittedLog->causer?->name,
                'acted_at' => $submittedLog->created_at,
                'status' => 'submitted',
            ]);
        }

        return Pdf::loadView('annualticket::pdf.annual_ticket', ['ticket' => $ticket, 'approvals' => $approvals])->setPaper('a4');
    }

    public function historyById(int $id, User $user): array
    {
        return $this->history($this->repository->findByIdFor($user, $id));
    }

    public function history(AnnualTicket $ticket): array
    {
        return \Spatie\Activitylog\Models\Activity::with('causer')
            ->where('log_name', 'annual-ticket')
            ->where('subject_type', $ticket->getMorphClass())
            ->where('subject_id', $ticket->id)
            ->orderBy('id')
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'event' => $log->event,
                'description' => $log->description,
                'round' => $log->properties->get('round', 1),
                'role' => $log->properties->get('role'),
                'note' => $log->properties->get('note'),
                'remark' => $log->properties->get('remark'),
                'evidence' => $log->properties->get('evidence'),
                'documents' => $log->properties->get('documents', []),
                'flow' => $log->properties->get('flow'),
                'causer' => $log->causer?->name,
                'created_at' => $log->created_at,
            ])
            ->values()
            ->all();
    }
}
