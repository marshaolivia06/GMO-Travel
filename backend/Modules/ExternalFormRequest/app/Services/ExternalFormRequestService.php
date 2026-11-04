<?php

namespace Modules\ExternalFormRequest\Services;

use Modules\ExternalFormRequest\Models\ExternalFormRequest;
use Modules\ExternalFormRequest\Repositories\ExternalFormRequestRepository;
use Modules\UserManagement\Models\User;

class ExternalFormRequestService
{
    public function __construct(private ExternalFormRequestRepository $repository) {}

    public function listFor(User $user)
    {
        return $this->repository->allFor($user);
    }

    public function create(User $user, array $data, array $travelers): ExternalFormRequest
    {
        return $this->repository->create($user, $data, $travelers);
    }

    public function update(User $user, int $id, array $data, array $travelers): ExternalFormRequest
    {
        return $this->repository->update($user, $id, $data, $travelers);
    }

    public function history(User $user, int $id): array
    {
        return $this->repository->historyFor($this->repository->findFor($user, $id));
    }
}
