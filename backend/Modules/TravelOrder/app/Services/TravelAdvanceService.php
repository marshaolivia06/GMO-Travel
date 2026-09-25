<?php

declare(strict_types=1);

namespace Modules\TravelOrder\Services;

use Modules\TravelOrder\Repositories\TravelAdvanceRepository;

class TravelAdvanceService
{
    public function __construct(
        private TravelAdvanceRepository $travelAdvanceRepository
    ) {}

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
