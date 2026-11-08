<?php

namespace Modules\AnnualTicket\Services;

use Modules\AnnualTicket\Repositories\MasterDestinationRepository;

class MasterDestinationService
{
    public function __construct(private MasterDestinationRepository $repository) {}

    public function list()
    {
        return $this->repository->all();
    }

    public function create(array $data)
    {
        $data['destination_code'] = strtoupper(trim($data['destination_code']));

        return $this->repository->create($data);
    }

    public function update(int $id, array $data)
    {
        unset($data['destination_code']);

        return $this->repository->update($id, $data);
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }
}