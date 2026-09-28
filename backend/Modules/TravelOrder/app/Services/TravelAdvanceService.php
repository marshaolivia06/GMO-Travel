<?php

declare(strict_types=1);

namespace Modules\TravelOrder\Services;

use Modules\TravelOrder\Models\TravelAdvanceMaster;
use Modules\TravelOrder\Repositories\TravelAdvanceRepository;

class TravelAdvanceService
{
    public function __construct(
        private TravelAdvanceRepository $travelAdvanceRepository
    ) {}

    public function getAll()
    {
        return $this->travelAdvanceRepository->getAll();
    }

    public function findById(int $id)
    {
        return $this->travelAdvanceRepository->findById($id);
    }

    public function create(array $data): TravelAdvanceMaster
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

    public function findByRegionAndGrade(
        string $travelRegion,
        int $grade
    ): ?TravelAdvanceMaster {
        return $this->travelAdvanceRepository->findByRegionAndGrade(
            $travelRegion,
            $grade
        );
    }

    public function getDistinctRegions(): array
    {
    return $this->travelAdvanceRepository->getDistinctRegions();
    }

    public function getCountriesByRegion(string $travelRegion): array
    {
    return $this->travelAdvanceRepository->getCountriesByRegion($travelRegion);
    }

    public function findByRegionAndCurrency(
        string $travelRegion,
        string $currency
    ): ?TravelAdvanceMaster {
        return $this->travelAdvanceRepository->findByRegionAndCurrency(
            $travelRegion,
            $currency
        );
    }

    public function validateAdvance(
        string $travelRegion,
        string $currency,
        float $pocketMoney,
        float $mealAllowance
    ): array {
        if ($travelRegion === 'Indonesia') {
            return [
                'valid' => true,
                'message' => null,
            ];
        }

        $master = $this->travelAdvanceRepository->findByRegionAndCurrency(
            $travelRegion,
            $currency
        );

        if (!$master) {
            return [
                'valid' => false,
                'message' => 'Travel Advance master data not found.',
            ];
        }

        if ($pocketMoney > (float) $master->pocket_money_limit) {
            return [
                'valid' => false,
                'message' => 'Pocket Money exceeds the allowed limit.',
            ];
        }

        if ($mealAllowance > (float) $master->meal_allowance_limit) {
            return [
                'valid' => false,
                'message' => 'Meal Allowance exceeds the allowed limit.',
            ];
        }

        return [
            'valid' => true,
            'message' => null,
        ];
    }
}