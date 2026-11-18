<?php

namespace Modules\AuthorityMatrix\Services;

use Illuminate\Support\Collection;
use Modules\AuthorityMatrix\Models\AuthorityMatrixStep;
use Modules\MasterManagement\Models\Section;
use Modules\UserManagement\Models\User;

// Menentukan siapa approver untuk satu step matrix berdasarkan pengaturannya:
// supervisor spesifik -> section spesifik -> area/department -> semua user dengan role tsb.
// Step tanpa pengaturan apa pun match semua pemilik role (tidak dibatasi unit organisasi).
class MatrixStepApproverResolver
{
    // Kolom penanda orang di section sesuai role step: Sect Head -> section_head_id, Supervisor -> supervisor_id
    private function sectionUserColumn(?AuthorityMatrixStep $step): ?string
    {
        return match (strtolower(trim($step?->role?->name ?? ''))) {
            'sect head' => 'section_head_id',
            'supervisor' => 'supervisor_id',
            default => null,
        };
    }

    private function isConstrained(AuthorityMatrixStep $step): bool
    {
        return $step->section_id
            || $step->department_id
            || filled($step->area)
            || ($step->is_specific_supervisor && $step->supervisor_id);
    }

    // Semua user yang berhak memproses step ini (dipakai untuk pengecekan izin)
    public function approversFor(?AuthorityMatrixStep $step, string $roleName): Collection
    {
        if ($step?->is_specific_supervisor && $step->supervisor_id) {
            return User::whereKey($step->supervisor_id)->get();
        }

        if ($step?->section_id) {
            $column = $this->sectionUserColumn($step) ?? 'section_head_id';

            return User::whereKey(
                Section::where('id', $step->section_id)->value($column)
            )->get();
        }

        // Area / department spesifik -> orang pada section yang cocok (Sect Head / Supervisor)
        if ($step && ($step->department_id || filled($step->area)) && ($column = $this->sectionUserColumn($step))) {
            return User::whereIn('id', Section::query()
                ->when($step->area, fn ($query) => $query->where('area', $step->area))
                ->when($step->department_id, fn ($query) => $query->where('department_id', $step->department_id))
                ->whereNotNull($column)
                ->select($column))
                ->get();
        }

        return User::role($roleName)->get();
    }

    public function canAct(?AuthorityMatrixStep $step, string $roleName, User $user): bool
    {
        return $this->approversFor($step, $roleName)->contains('id', $user->id);
    }

    // Step terbatas (section/area/department/supervisor) yang ditugaskan langsung ke user ini
    // - dipakai untuk scope daftar dokumen yang perlu dia approve
    public function stepIdsMatchingUser(User $user): array
    {
        $userSections = Section::where(fn ($query) => $query
            ->where('section_head_id', $user->id)
            ->orWhere('supervisor_id', $user->id))
            ->get();

        if ($userSections->isEmpty()) {
            return [];
        }

        return AuthorityMatrixStep::with('role')->get()
            ->filter(fn ($step) => $this->isConstrained($step) && $this->stepMatchesUser($step, $user, $userSections))
            ->pluck('id')
            ->all();
    }

    private function stepMatchesUser(AuthorityMatrixStep $step, User $user, Collection $userSections): bool
    {
        if ($step->is_specific_supervisor && $step->supervisor_id) {
            return (string) $step->supervisor_id === (string) $user->id;
        }

        $column = $this->sectionUserColumn($step);

        if (! $column) {
            return false;
        }

        return $userSections->contains(fn (Section $section) => (string) $section->{$column} === (string) $user->id
            && (! $step->section_id || (string) $section->id === (string) $step->section_id)
            && (! filled($step->area) || $section->area === $step->area)
            && (! $step->department_id || (string) $section->department_id === (string) $step->department_id));
    }

    // Role dari step tanpa batasan apa pun yang dipegang user -> dia approver umum untuk role itu
    public function unconstrainedRoleIdsFor(User $user): array
    {
        $roleIds = $user->roles->pluck('id');

        if ($roleIds->isEmpty()) {
            return [];
        }

        return AuthorityMatrixStep::query()
            ->whereIn('role_id', $roleIds)
            ->whereNull('section_id')
            ->whereNull('department_id')
            ->whereNull('area')
            ->where(fn ($query) => $query->where('is_specific_supervisor', false)->orWhereNull('supervisor_id'))
            ->pluck('role_id')
            ->unique()
            ->values()
            ->all();
    }
}
