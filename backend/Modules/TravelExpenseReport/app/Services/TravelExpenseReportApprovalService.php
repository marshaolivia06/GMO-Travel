<?php

namespace Modules\TravelExpenseReport\Services;

use Illuminate\Support\Facades\DB;
use Modules\AuthorityMatrix\Models\AuthorityMatrix;
use Modules\AuthorityMatrix\Services\MatrixStepApproverResolver;
use Modules\TravelExpenseReport\Models\TravelExpenseReport;
use Modules\UserManagement\Models\User;
use Spatie\Activitylog\Models\Activity;

// Alur approval TER mengikuti authority matrix "Travel Expense Report" (dinamis sesuai role requester).
// Status per tahap: action check -> awaiting_review_<role>, approve -> awaiting_approval_<role>.
// Semua tahap selesai -> approved; ada yang mengembalikan -> revision_required.
class TravelExpenseReportApprovalService
{
    private const DOCUMENT_TYPE = 'Travel Expense Report';

    public function __construct(private MatrixStepApproverResolver $resolver) {}

    private function resolveMatrix(User $submitter)
    {
        $roleIds = $submitter->roles->pluck('id');

        $matrix = AuthorityMatrix::with('steps.role')
            ->where('document_type', self::DOCUMENT_TYPE)
            ->where('status', 1)
            ->where(function ($query) use ($roleIds) {
                $query->whereNull('requester_role_id')->orWhereIn('requester_role_id', $roleIds);
            })
            // matrix spesifik requester role diprioritaskan di atas matrix generik
            ->orderByRaw('requester_role_id IS NULL')
            ->first();

        if (!$matrix || $matrix->steps->isEmpty()) {
            throw new \RuntimeException('The "Travel Expense Report" authority matrix has not been created or does not yet have any steps defined.');
        }

        return $matrix->steps->sortBy('step')->values();
    }

    // Validasi awal: matrix harus tersedia sebelum TER dipersist - matrix belum ada -> gagal dengan pesan jelas
    public function assertMatrixFor(User $submitter): void
    {
        $this->resolveMatrix($submitter);
    }

    public function initFlow(TravelExpenseReport $report): void
    {
        $submitter = User::with('roles')->find($report->employee_id);
        $submitterRole = $submitter?->roles->first();

        if (!$submitterRole) {
            throw new \RuntimeException('The applicant does not yet have a role.');
        }

        $stages = $this->resolveMatrix($submitter);

        DB::transaction(function () use ($report, $submitter, $submitterRole, $stages) {
            // Riwayat putaran sebelumnya dipertahankan, putaran baru lanjut dari sequence terakhir
            $sequence = ($report->approvals()->max('sequence') ?? 0) + 1;

            $report->approvals()->create(['sequence' => $sequence++, 'role_id' => $submitterRole->id, 'user_id' => $report->employee_id, 'status' => 'submitted', 'action_type' => null, 'acted_at' => now()]);

            foreach ($stages as $stage) {
                $report->approvals()->create([
                    'sequence' => $sequence++,
                    'authority_matrix_step_id' => $stage->id,
                    'action_type' => $stage->action_type,
                    'role_id' => $stage->role_id,
                    'status' => 'pending',
                ]);
            }

            $report->update(['status' => $this->statusForStage($stages->first())]);
        });

        $this->logActivity($report, $submitter, 'submitted', 'Travel Expense Report submitted', ['role' => $submitterRole->name]);
    }

    // Singkatan unit organisasi untuk slug status - tambahkan unit lain di sini
    private const STATUS_ABBREVIATIONS = [
        'Operation Control' => 'oc',
    ];

    private function statusForStage($stage): string
    {
        $prefix = $stage->action_type === 'approve' ? 'awaiting_approval' : 'awaiting_review';

        // Sumber nama status: unit organisasi step (section > area > department), fallback nama role
        $step = $stage->matrixStep ?? $stage;
        $source = $step->section?->name
            ?? (filled($step->area) ? $step->area : null)
            ?? $step->department?->name
            ?? $stage->role?->name
            ?? '';

        $source = self::STATUS_ABBREVIATIONS[$source] ?? $source;

        // Normalisasi: "Admin-GMO" maupun "Admin GMO" sama-sama menjadi admin_gmo
        $slug = preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($source)));

        // Role "Admin-GMO" cukup ditulis gmo sesuai label status yang diharapkan
        if ($slug === 'admin_gmo') {
            $slug = 'gmo';
        }

        return $prefix.'_'.trim($slug, '_');
    }

    public function currentStage(TravelExpenseReport $report)
    {
        if (!str_starts_with($report->status, 'awaiting_')) {
            return null;
        }

        return $report->approvals()->with('role', 'matrixStep.section', 'matrixStep.department')->where('status', 'pending')->orderBy('sequence')->first();
    }

    public function currentRound(TravelExpenseReport $report): int
    {
        return Activity::where('log_name', 'travel-expense-report')
            ->where('subject_type', $report->getMorphClass())
            ->where('subject_id', $report->id)
            ->where('event', 'submitted')
            ->count();
    }

    // Activity log TER - submit membuka round baru
    public function logActivity(TravelExpenseReport $report, ?User $causer, string $event, string $description, array $props = []): void
    {
        $round = max(1, $this->currentRound($report) + ($event === 'submitted' ? 1 : 0));

        activity('travel-expense-report')
            ->performedOn($report)
            ->causedBy($causer)
            ->event($event)
            ->withProperties(array_merge($props, ['round' => $round]))
            ->log($description);
    }

    public function canProcess(TravelExpenseReport $report, User $user): bool
    {
        $stage = $this->currentStage($report);

        if (!$stage) {
            return false;
        }

        // Mengikuti pengaturan step matrix: section/department/area/supervisor spesifik
        // menentukan siapa approver-nya; step tanpa batasan -> semua pemilik role
        $stage->loadMissing('matrixStep.section', 'matrixStep.department');

        return $this->resolver->canAct($stage->matrixStep, $stage->role->name, $user);
    }

    public function check(TravelExpenseReport $report, User $user, ?string $note = null): void
    {
        $this->advance($report, $user, 'check', 'checked', 'Travel Expense Report checked', $note);
    }

    public function approve(TravelExpenseReport $report, User $user, ?string $note = null): void
    {
        $this->advance($report, $user, 'approve', 'approved', 'Travel Expense Report approved', $note);
    }

    private function advance(TravelExpenseReport $report, User $user, string $expectedAction, string $status, string $description, ?string $note = null): void
    {
        $stage = $this->currentStage($report);

        abort_if(!$stage, 422, 'The Travel Expense Report cannot be processed at the current stage.');
        abort_if($stage->action_type !== $expectedAction, 422, 'The current stage requires a "'.$expectedAction.'" action.');
        abort_unless($this->canProcess($report, $user), 403, 'You are not allowed to process this Travel Expense Report.');

        DB::transaction(function () use ($report, $user, $stage, $status, $description, $note) {
            $stage->update(['status' => $status, 'user_id' => $user->id, 'note' => $note, 'acted_at' => now()]);

            $next = $report->approvals()->with('role')->where('status', 'pending')->orderBy('sequence')->first();

            $this->logActivity($report, $user, $status, $description, [
                'sequence' => $stage->sequence,
                'role' => $stage->role->name,
                'note' => $note,
                'final' => !$next,
            ]);

            if ($next) {
                $report->update(['status' => $this->statusForStage($next)]);

                return;
            }

            // Semua step matrix selesai -> claim approved
            $report->update(['status' => TravelExpenseReport::STATUS_APPROVED]);
        });
    }

    public function revision(TravelExpenseReport $report, User $user, string $remark): void
    {
        $stage = $this->currentStage($report);

        abort_if(!$stage, 422, 'The Travel Expense Report cannot be processed at the current stage.');
        abort_unless($this->canProcess($report, $user), 403, 'You are not allowed to process this Travel Expense Report.');
        abort_if(trim($remark) === '', 422, 'Remark is required.');

        DB::transaction(function () use ($report, $user, $stage, $remark) {
            $stage->update(['status' => 'revision', 'user_id' => $user->id, 'note' => $remark, 'acted_at' => now()]);

            $this->logActivity($report, $user, 'revision', 'Travel Expense Report returned for revision', ['sequence' => $stage->sequence, 'role' => $stage->role->name, 'remark' => $remark]);

            $report->approvals()->where('status', 'pending')->update(['status' => 'cancelled']);
            $report->update(['status' => TravelExpenseReport::STATUS_REVISION_REQUIRED]);
        });
    }

    public function latestRoundApprovals(TravelExpenseReport $report)
    {
        $approvals = $report->approvals()->with(['role', 'user', 'matrixStep.section', 'matrixStep.department'])->get();
        $lastSubmitted = $approvals->where('status', 'submitted')->max('sequence');

        return $lastSubmitted === null ? $approvals : $approvals->where('sequence', '>=', $lastSubmitted)->values();
    }
}
