<?php

namespace Modules\TravelOrder\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\TravelOrder\Models\TravelOrderGroup;
use Modules\TravelOrder\Repositories\TravelOrderGroupRepository;
use Illuminate\Database\Eloquent\Collection;

class TravelOrderGroupService
{
    public function __construct(protected TravelOrderGroupRepository $repository) {}

    public function list(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(int $id): TravelOrderGroup
    {
        return $this->repository->findOrFail($id);
    }

    public function myGroups(int $userId): Collection
    {
    return $this->repository->myGroups($userId);
    }

    public function create(array $data): TravelOrderGroup
    {
        $memberIds = array_values(array_unique($data['members'] ?? []));
        unset($data['members']);
        if (count($memberIds) < 2) throw ValidationException::withMessages(['members' => 'Group harus memiliki minimal 2 anggota.']);

        $data['created_by'] = auth()->id();
        $data['status'] = 'open';
        $data['opened_at'] = now();

        return DB::transaction(function () use ($data, $memberIds) {
            $group = $this->repository->create($data);
            $this->repository->addMembers($group, $memberIds);
            return $this->repository->findOrFail($group->id);
        });
    }

    public function update(int $id, array $data): TravelOrderGroup
    {
        $group = $this->repository->findOrFail($id);
        $this->ensureStatus($group, ['open'], 'Hanya group berstatus open yang dapat diubah.');

        $memberIds = isset($data['members']) ? array_values(array_unique($data['members'])) : null;
        unset($data['members'], $data['departments'], $data['status'], $data['created_by'], $data['opened_at'], $data['closed_at']);
        if ($memberIds !== null && count($memberIds) < 2) throw ValidationException::withMessages(['members' => 'Group harus memiliki minimal 2 anggota.']);

        return DB::transaction(function () use ($group, $data, $memberIds) {
            $this->repository->update($group, $data);
            if ($memberIds !== null) {
                $removedIds = $group->members()->whereNotIn('user_id', $memberIds)->pluck('user_id');
                $this->ensureMembersCanBeRemoved($group, $removedIds);

                $group->members()->whereNotIn('user_id', $memberIds)->delete();
                $this->repository->addMembers($group, $memberIds);
            }
            return $this->repository->findOrFail($group->id);
        });
    }

    public function delete(int $id): void
    {
        $group = $this->repository->findOrFail($id);
        $this->ensureStatus($group, ['open'], 'Hanya group berstatus open yang dapat dihapus.');
        $this->repository->delete($group);
    }

    public function open(int $id): TravelOrderGroup
    {
        $group = $this->repository->findOrFail($id);
        $this->ensureStatus($group, ['open'], 'Group sudah berstatus open.');
        return $group;
    }

    public function close(int $id): TravelOrderGroup
    {
        $group = $this->repository->findOrFail($id);
        $this->ensureStatus($group, ['open'], 'Hanya group berstatus open yang dapat ditutup.');
        return $this->repository->update($group, ['status' => 'closed', 'closed_at' => now()]);
    }

    public function addMembers(int $id, array $userIds): TravelOrderGroup
    {
        $group = $this->repository->findOrFail($id);
        $this->ensureStatus($group, ['open'], 'Anggota hanya dapat diubah pada group berstatus open.');
        $this->repository->addMembers($group, array_values(array_unique($userIds)));
        if ($group->members()->count() < 2) throw ValidationException::withMessages(['members' => 'Group harus memiliki minimal 2 anggota.']);
        return $this->repository->findOrFail($id);
    }

    public function removeMember(int $id, int $userId): TravelOrderGroup
    {
        $group = $this->repository->findOrFail($id);
        $this->ensureStatus($group, ['open'], 'Anggota tidak dapat diubah pada group yang sudah closed.');
        if ($group->members()->count() <= 2) throw ValidationException::withMessages(['members' => 'Group harus memiliki minimal 2 anggota.']);
        $this->ensureMembersCanBeRemoved($group, collect([$userId]));
        $this->repository->removeMember($group, $userId);
        return $this->repository->findOrFail($id);
    }

    protected function ensureStatus(TravelOrderGroup $group, array $allowed, string $message): void
    {
        if (!in_array($group->status, $allowed, true)) throw ValidationException::withMessages(['status' => $message]);
    }

    protected function ensureMembersCanBeRemoved(TravelOrderGroup $group, $userIds): void
    {
        if ($userIds->isEmpty()) return;

        $hasSubmitted = $group->travelOrders()
            ->whereIn('user_id', $userIds)
            ->where('status', '!=', 'draft')
            ->exists();

        if ($hasSubmitted) throw ValidationException::withMessages(['members' => 'Anggota yang sudah mengajukan Travel Order tidak dapat dihapus dari group.']);
    }
}
