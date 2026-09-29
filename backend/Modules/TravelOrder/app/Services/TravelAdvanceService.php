<?php

declare(strict_types=1);

namespace Modules\TravelOrder\Services;

use Modules\TravelOrder\Models\TravelAdvanceMaster;
use Modules\TravelOrder\Repositories\TravelAdvanceRepository;

class TravelAdvanceService
{
    // Nama region Indonesia di tabel master travel_advance_masters
    private const DOMESTIC_REGION = 'Domestic';

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

    /**
     * Batas total = limit master (per hari) x jumlah hari perjalanan.
     * Indonesia dicari berdasarkan grade user, region lain masih berdasarkan mata uang.
     */
    public function validateAdvance(
        string $travelRegion,
        ?string $currency,
        float $pocketMoney,
        float $mealAllowance,
        int $grade,
        int $days
    ): array {
        $master = $travelRegion === 'Indonesia'
            ? $this->travelAdvanceRepository->findByRegionAndGrade(self::DOMESTIC_REGION, $grade)
            : $this->travelAdvanceRepository->findByRegionAndCurrency($travelRegion, (string) $currency);

        if (! $master) {
            return [
                'valid' => false,
                'message' => 'Travel Advance master data not found.',
            ];
        }

        $days = max($days, 1);

        if ($pocketMoney > (float) $master->pocket_money_limit * $days) {
            return [
                'valid' => false,
                'message' => 'Pocket Money exceeds the allowed limit.',
            ];
        }

        if ($mealAllowance > (float) $master->meal_allowance_limit * $days) {
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