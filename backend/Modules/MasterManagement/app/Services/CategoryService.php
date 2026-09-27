<?php

declare(strict_types=1);

namespace Modules\TravelOrder\Services;

use Modules\TravelOrder\Repositories\TravelAdvanceRepository;

class TravelAdvanceMasterService
{
    public function __construct(
        private TravelAdvanceRepository $travelAdvanceRepository
    ) {
    }

    public function getAll()
    {
        return $this->travelAdvanceRepository->getAll();
    }

    public function findById(int $id)
    {
        return $this->travelAdvanceRepository->findById($id);
    }

    public function create(array $data)
    {
        return $this->travelAdvanceRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->travelAdvanceRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->travelAdvanceRepository->delete($id);
    }

    public function findByRegionAndCurrency(
        string $travelRegion,
        string $currency
    ) {
        return $this->travelAdvanceRepository->findByRegionAndCurrency(
            $travelRegion,
            $currency
        );
    }
}