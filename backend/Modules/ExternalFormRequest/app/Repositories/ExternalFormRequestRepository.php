<?php

namespace Modules\ExternalFormRequest\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\AuthorityMatrix\Models\AuthorityMatrix;
use Modules\ExternalFormRequest\Emails\ExternalFormReleasedMail;
use Modules\ExternalFormRequest\Models\ExternalFormApproval;
use Modules\ExternalFormRequest\Models\ExternalFormEmail;
use Modules\ExternalFormRequest\Models\ExternalFormRequest;
use Modules\MasterManagement\Models\Department;
use Modules\MasterManagement\Models\Section;
use Modules\UserManagement\Models\User;
use Spatie\Activitylog\Models\Activity;
use Throwable;

class ExternalFormRequestRepository
{
    private const LOG_NAME = 'external-form-request';

    // Daftar user ber-role Div Head untuk dropdown review Admin-GMO
    public function divHeads(): Collection
    {
        return User::query()
            ->where('status', 1)
            ->whereHas('roles', fn ($query) => $query->where('name', 'Div Head'))
            ->orderBy('name')
            ->get(['id', 'name']);
    }
    public function allFor(User $user): Collection
    {
        return ExternalFormRequest::query()
            ->with(['employee.eligibleDestination', 'travelers', 'approvals', 'emails'])
            ->when(! $user->hasRole('Admin'), fn ($query) => $query->where(function ($query) use ($user) {
                $query->where('employee_id', $user->id)
                    ->orWhereHas('approvals', fn ($approvals) => $approvals
                        ->where('user_id', $user->id)
                        ->where('status', 'pending')
                        // Hanya giliran berjalan - step pending berikutnya tidak dihitung
                        ->whereNotExists(function ($sub) {
                            $sub->select(DB::raw(1))
                                ->from('external_form_approvals as prev')
                                ->whereColumn('prev.external_form_request_id', 'external_form_approvals.external_form_request_id')
                                ->where('prev.status', 'pending')
                                ->whereColumn('prev.step', '<', 'external_form_approvals.step');
                        }))
                    // Admin-GMO melihat semua pengajuan yang sudah melewati/mencapai tahapnya:
                    // awaiting review, menunggu div head, booking & payment, released, rejected, revision
                    ->when($user->hasRole('Admin-GMO'), fn ($q) => $q->orWhereNotIn('status', [
                        ExternalFormRequest::STATUS_DRAFT,
                        ExternalFormRequest::STATUS_AWAITING_DEPT_HEAD,
                    ]));
            }))
            ->latest()
            ->get()
            ->each(function (ExternalFormRequest $form) use ($user) {
                $pending = $form->approvals->first(
                    fn (ExternalFormApproval $approval) => $approval->status === 'pending' && $approval->user_id === $user->id,
                );

                $form->setAttribute('can_approve', (bool) $pending);
                $form->setAttribute('pending_action', $pending?->action_type === 'check' ? 'Review' : 'Approve');
                // Tahap booking & payment: muncul lagi ke Admin-GMO untuk di-release
                $form->setAttribute('can_process', $user->hasRole('Admin-GMO') && $form->status === ExternalFormRequest::STATUS_BOOKING_PAYMENT);
            });
    }

    public function findFor(User $user, int $id): ExternalFormRequest
    {
        return ExternalFormRequest::query()
            ->with(['travelers', 'approvals'])
            ->when(! $user->hasRole('Admin'), fn ($query) => $query->where(function ($query) use ($user) {
                $query->where('employee_id', $user->id)
                    ->orWhereHas('approvals', fn ($approvals) => $approvals->where('user_id', $user->id));
            }))
            ->findOrFail($id);
    }

    public function historyFor(ExternalFormRequest $request): array
    {
        // Draft tidak masuk history - log dimulai dari submit (Requested)
        return Activity::with('causer')
            ->where('log_name', self::LOG_NAME)
            ->where('subject_type', $request->getMorphClass())
            ->where('subject_id', $request->id)
            ->where('event', '!=', 'draft')
            ->orderBy('id')
            ->get()
            ->map(fn (Activity $log) => [
                'id' => $log->id,
                'event' => $log->event,
                'description' => $log->description,
                'properties' => $log->properties->get('status') ? ['status' => $log->properties->get('status')] : [],
                'role' => $log->properties->get('role') ?? '-',
                'round' => $log->properties->get('round', 1),
                'note' => $log->properties->get('note'),
                'remark' => $log->properties->get('remark'),
                'documents' => $log->properties->get('documents', []),
                'causer' => $log->causer?->name,
                'created_at' => $log->created_at,
            ])
            ->values()
            ->all();
    }

    public function approve(User $user, int $id, array $data = []): ExternalFormRequest
    {
        return $this->decide($user, $id, 'approve', null, $data);
    }

    public function revision(User $user, int $id, ?string $remark = null): ExternalFormRequest
    {
        return $this->decide($user, $id, 'revision', $remark);
    }

    public function reject(User $user, int $id, ?string $remark = null): ExternalFormRequest
    {
        return $this->decide($user, $id, 'reject', $remark);
    }

    public function decide(User $user, int $id, string $action, ?string $remark = null, array $extra = []): ExternalFormRequest
    {
        return DB::transaction(function () use ($user, $id, $action, $remark, $extra) {
            $form = ExternalFormRequest::query()
                ->with('approvals')
                ->whereHas('approvals', fn ($approvals) => $approvals
                    ->where('user_id', $user->id)
                    ->where('status', 'pending'))
                ->findOrFail($id);

            $firstPending = $form->approvals->sortBy('step')->first(
                fn (ExternalFormApproval $approval) => $approval->status === 'pending',
            );

            $approval = $form->approvals->first(
                fn (ExternalFormApproval $row) => $row->status === 'pending' && $row->user_id === $user->id,
            );

            // Wajib diproses sesuai urutan step authority matrix
            if (! $approval || $approval->id !== $firstPending?->id) {
                abort(422, 'This request is not awaiting your approval yet.');
            }

            $role = $approval->role?->name ?? $approval->label;

            if ($action === 'revision') {
                $approval->update(['status' => 'revision', 'acted_at' => now()]);

                // Sisa step pending berikutnya dibatalkan - round baru dibuat saat resubmit
                $form->approvals()->where('status', 'pending')->update(['status' => 'cancelled']);

                $form->update(['status' => 'revision_required']);

                $this->logActivity($user, $form->refresh(), 'revision',
                    'External form request returned for revision by ' . str_replace(' ', '.', (string) $role) . '.', ['note' => $remark]);

                return $form->refresh()->load(['travelers', 'approvals']);
            }

            if ($action === 'reject') {
                $approval->update(['status' => 'rejected', 'acted_at' => now()]);

                $form->approvals()->where('status', 'pending')->update(['status' => 'cancelled']);

                $form->update(['status' => 'rejected']);

                $this->logActivity($user, $form->refresh(), 'rejected',
                    'External form request rejected by ' . str_replace(' ', '.', (string) $role) . '.', ['note' => $remark]);

                return $form->refresh()->load(['travelers', 'approvals']);
            }

            $isReview = $approval->action_type === 'check';

            // Step berjalan ditandai dulu sebelum refresh, supaya perhitungan step berikutnya tidak membaca status lama
            $approval->update([
                'status' => $isReview ? 'reviewed' : 'approved',
                'acted_at' => now(),
            ]);

            // Data GMO Travel Detail (khusus review Admin-GMO) + reassign approver Div Head pilihan
            if ($isReview && $extra !== []) {
                $form->update(collect($extra)->only([
                    'operator', 'flight_vessel_no', 'departure_atd', 'departure_ata',
                    'return_operator', 'return_flight_vessel_no', 'return_atd', 'return_ata',
                    'hotel', 'room_type',
                ])->filter()->all());
                $form->refresh();
            }

            $next = $form->approvals->sortBy('step')->first(
                fn (ExternalFormApproval $row) => $row->status === 'pending',
            );

            $normalize = fn (string $value) => strtolower(preg_replace('/[^a-z0-9]+/i', '', $value));

            // Div Head yang dipilih Admin-GMO menjadi approver berikutnya
            if ($isReview && ! empty($extra['div_head_id']) && $next
                && $normalize($next->role?->name ?? $next->label ?? '') === 'divhead') {
                $next->update(['user_id' => $extra['div_head_id']]);
            }

            $statusByRole = [
                'depthead' => ExternalFormRequest::STATUS_AWAITING_DEPT_HEAD,
                'admingmo' => ExternalFormRequest::STATUS_AWAITING_REVIEW_GMO,
                'divhead' => ExternalFormRequest::STATUS_AWAITING_DIV_HEAD,
            ];

            $form->update([
                'status' => $next
                    ? ($statusByRole[$normalize($next->role?->name ?? $next->label ?? '')] ?? $form->status)
                    : ExternalFormRequest::STATUS_BOOKING_PAYMENT,
            ]);

            $this->logActivity($user, $form->refresh(), $isReview ? 'checked' : 'approved',
                $isReview
                    ? 'External form request reviewed by Admin-GMO.'
                    : 'External form request approved by ' . str_replace(' ', '.', (string) $role) . '.',
                ! empty($extra['remark']) ? ['note' => $extra['remark']] : []);

            return $form->refresh()->load(['travelers', 'approvals']);
        });
    }

    // Release = tahap terakhir: Admin-GMO memproses booking & payment, kirim email ke requester, status released
    public function release(User $user, int $id, array $data, ?string $attachmentPath = null, ?string $attachmentName = null): ExternalFormRequest
    {
        return DB::transaction(function () use ($user, $id, $data, $attachmentPath, $attachmentName) {
            $form = ExternalFormRequest::query()->findOrFail($id);

            abort_if($form->status !== ExternalFormRequest::STATUS_BOOKING_PAYMENT,
                422, 'This request is not in booking & payment stage.');

            $form->update(collect($data)->only([
                'operator', 'flight_vessel_no', 'departure_atd', 'departure_ata',
                'return_operator', 'return_flight_vessel_no', 'return_atd', 'return_ata',
                'hotel', 'room_type',
                'departure_booking_code', 'return_booking_code',
                'reservation_confirmation_no',
            ])->filter()->all());

            // Validasi nomor sesuai tipe pengajuan
            if ($form->type === ExternalFormRequest::TYPE_HOTEL_RESERVATION) {
                abort_if(empty($form->reservation_confirmation_no), 422, 'Reservation / confirmation number is required.');
            } else {
                abort_if(empty($form->departure_booking_code), 422, 'Departure booking code is required.');
                if ($form->trip_type === 'round_trip') {
                    abort_if(empty($form->return_booking_code), 422, 'Return booking code is required.');
                }
            }

            // Kirim email ke requester (log dicatat walau pengiriman gagal)
            $to = $this->normalizeEmails($data['email']['to'] ?? '');
            $cc = $this->normalizeEmails($data['email']['cc'] ?? '');

            abort_if($to->isEmpty(), 422, 'Email recipient is required.');

            $emailLog = ExternalFormEmail::create([
                'external_form_request_id' => $form->id,
                'sent_by' => $user->id,
                'to' => json_encode($to->values()->all()),
                'cc' => $cc->isNotEmpty() ? json_encode($cc->values()->all()) : null,
                'subject' => $data['email']['subject'],
                'message' => $data['email']['message'] ?? '',
                'attachment_path' => $attachmentPath,
                'status' => 'pending',
            ]);

            $emailStatus = 'failed';

            try {
                $mail = new ExternalFormReleasedMail($form->refresh(), $attachmentPath);
                $mail->subject($data['email']['subject']);
                $mail->with(['emailMessage' => $data['email']['message']]);

                $mailer = Mail::to($to->all());
                if ($cc->isNotEmpty()) {
                    $mailer->cc($cc->all());
                }
                $mailer->send($mail);

                $emailLog->update(['status' => 'sent', 'sent_at' => now()]);
                $emailStatus = 'sent';
            } catch (Throwable $e) {
                $emailLog->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            }

            $form->update(['status' => ExternalFormRequest::STATUS_RELEASED]);

            // Lampiran release dicatat di history log sebagai dokumen (nama + URL) seperti annual ticket
            $documents = $attachmentPath
                ? [['name' => $attachmentName ?: basename($attachmentPath), 'url' => Storage::disk('public')->url($attachmentPath)]]
                : [];

            $this->logActivity($user, $form->refresh(), $emailStatus === 'sent' ? 'email_sent' : 'email_failed',
                $emailStatus === 'sent'
                    ? 'Release email sent to requester.'
                    : 'Release email failed to send.',
                $documents ? ['documents' => $documents] : []);

            $this->logActivity($user, $form->refresh(), 'released',
                'External form request released by Admin-GMO.');

            return $form->refresh()->load(['travelers', 'approvals', 'emails']);
        });
    }

    // "a@x.com, b@y.com" -> Collection alamat email yang valid
    private function normalizeEmails(?string $raw): \Illuminate\Support\Collection
    {
        return collect(explode(',', (string) $raw))
            ->map(fn ($email) => trim($email))
            ->filter(fn ($email) => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique();
    }

    // Round = jumlah kali submit; event submit membuka round baru
    public function currentRound(ExternalFormRequest $request): int
    {
        return Activity::where('log_name', self::LOG_NAME)
            ->where('subject_type', $request->getMorphClass())
            ->where('subject_id', $request->id)
            ->where('event', 'submit')
            ->count();
    }

    private function logActivity(User $user, ExternalFormRequest $request, string $event, string $description, array $props = []): void
    {
        $round = max(1, $this->currentRound($request) + ($event === 'submit' ? 1 : 0));

        activity(self::LOG_NAME)
            ->performedOn($request)
            ->causedBy($user)
            ->event($event)
            ->withProperties(array_merge([
                'status' => $request->status,
                'role' => $user->roles->first()?->name,
                'round' => $round,
            ], $props))
            ->log($description);
    }

    public function create(User $user, array $data, array $travelers): ExternalFormRequest
    {
        return DB::transaction(function () use ($user, $data, $travelers) {
            $request = ExternalFormRequest::create([
                'employee_id' => $user->id,
                'type' => $data['type'],
                'status' => $data['action'] === 'draft'
                    ? ExternalFormRequest::STATUS_DRAFT
                    : ExternalFormRequest::STATUS_AWAITING_DEPT_HEAD,
                'document_number' => $data['action'] === 'draft' ? null : ExternalFormRequest::generateDocumentNumber(),
                'transport_mode' => $data['transport_mode'] ?? null,
                'vendor_customer' => $data['vendor_customer'] ?? null,
                'purpose' => $data['purpose'] ?? null,
                'trip_type' => $data['trip_type'] ?? null,
                'travel_from' => $data['travel_from'] ?? null,
                'departure_date' => $data['departure_date'] ?? null,
                'travel_to' => $data['travel_to'] ?? null,
                'return_date' => $data['return_date'] ?? null,
                'check_in_date' => $data['check_in_date'] ?? null,
                'check_out_date' => $data['check_out_date'] ?? null,
                'remark' => $data['remark'] ?? null,
            ]);

            foreach ($travelers as $traveler) {
                $request->travelers()->create(['name' => $traveler['name']]);
            }

            $this->logActivity($user, $request, $data['action'], $data['action'] === 'draft'
                ? 'External form request saved as draft.'
                : 'External form request submitted.');

            if ($data['action'] === 'submit') {
                $this->createApprovalChain($user, $request);
            }

            return $request->load('travelers');
        });
    }

    public function update(User $user, int $id, array $data, array $travelers): ExternalFormRequest
    {
        return DB::transaction(function () use ($user, $id, $data, $travelers) {
            $request = ExternalFormRequest::query()
                ->where('employee_id', $user->id)
                ->whereIn('status', [ExternalFormRequest::STATUS_DRAFT, 'revision_required'])
                ->findOrFail($id);

            $request->update([
                'type' => $data['type'],
                'status' => $data['action'] === 'draft'
                    ? ExternalFormRequest::STATUS_DRAFT
                    : ExternalFormRequest::STATUS_AWAITING_DEPT_HEAD,
                'document_number' => $data['action'] === 'draft' ? null : ExternalFormRequest::generateDocumentNumber(),
                'transport_mode' => $data['transport_mode'] ?? null,
                'vendor_customer' => $data['vendor_customer'] ?? null,
                'purpose' => $data['purpose'] ?? null,
                'trip_type' => $data['trip_type'] ?? null,
                'travel_from' => $data['travel_from'] ?? null,
                'departure_date' => $data['departure_date'] ?? null,
                'travel_to' => $data['travel_to'] ?? null,
                'return_date' => $data['return_date'] ?? null,
                'check_in_date' => $data['check_in_date'] ?? null,
                'check_out_date' => $data['check_out_date'] ?? null,
                'remark' => $data['remark'] ?? null,
                // Detail GMO milik round sebelumnya dibersihkan saat revisi - diisi ulang saat review GMO berikutnya
                'operator' => null,
                'flight_vessel_no' => null,
                'departure_atd' => null,
                'departure_ata' => null,
                'return_operator' => null,
                'return_flight_vessel_no' => null,
                'return_atd' => null,
                'return_ata' => null,
                'hotel' => null,
                'room_type' => null,
            ]);

            $request->travelers()->delete();

            foreach ($travelers as $traveler) {
                $request->travelers()->create(['name' => $traveler['name']]);
            }

            $this->logActivity($user, $request->refresh(), $data['action'], $data['action'] === 'draft'
                ? 'Draft updated.'
                : 'External form request resubmitted.');

            if ($data['action'] === 'submit') {
                $this->createApprovalChain($user, $request);
            }

            return $request->load('travelers');
        });
    }

    // Rantai approval dibangun saat submit per round - baris lama dipertahankan sebagai history,
    // pending yang menggantung dibatalkan, requester + tahapan authority matrix "External Form Request"
    private function createApprovalChain(User $user, ExternalFormRequest $form): void
    {
        $form->approvals()->where('status', 'pending')->update(['status' => 'cancelled']);

        $round = max(1, $this->currentRound($form));

        $userRole = $user->roles->first();

        $form->approvals()->create([
            'round' => $round,
            'step' => 0,
            'role_id' => $userRole?->id,
            'label' => $userRole?->name,
            'action_type' => 'submit',
            'user_id' => $user->id,
            'status' => 'submitted',
            'acted_at' => now(),
        ]);

        $matrix = AuthorityMatrix::with('steps.role')
            ->where('document_type', 'External Form Request')
            ->where('status', true)
            ->first();

        if (! $matrix) {
            return;
        }

        $approvers = $this->resolveApprovers($form);

        $normalize = fn (string $value) => strtolower(preg_replace('/[^a-z0-9]+/i', '', $value));

        foreach ($matrix->steps->sortBy('step') as $step) {
            $key = $normalize($step->role?->name ?? $step->label ?? '');

            $form->approvals()->create([
                'round' => $round,
                'step' => $step->step,
                'role_id' => $step->role_id,
                'label' => $step->label ?: str_replace(' ', '.', $step->role?->name ?? '-'),
                'action_type' => $step->action_type,
                'user_id' => $approvers[$key] ?? null,
                'status' => 'pending',
            ]);
        }
    }

    // Approver mengikuti requester: Sect.Head -> section -> department -> Dept.Head / Div.Head, Admin-GMO dari role
    private function resolveApprovers(ExternalFormRequest $form): array
    {
        $section = Section::with('department')->where('section_head_id', $form->employee_id)->first();

        $department = $section?->department
            ?? Department::find($form->employee?->department_id)
            ?? Department::where('dept_head_id', $form->employee_id)->first();

        $divisionHead = $department?->divisionHead ? User::find($department->divisionHead->division_head_id) : null;

        $adminGmo = User::whereHas('roles', fn ($query) => $query->where('name', 'Admin-GMO'))->first();

        return [
            'depthead' => $department?->deptHead?->id,
            'admingmo' => $adminGmo?->id,
            'divhead' => $divisionHead?->id,
        ];
    }
}
