<?php

namespace Modules\TravelOrder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\TravelOrder\Repositories\TravelAdvanceRepository;

class TravelAdvanceMasterController extends Controller
{
    public function __construct(
        private TravelAdvanceRepository $repository
    ) {
    }

    public function limit(Request $request): JsonResponse
    {
        $request->validate([
            'travel_region' => ['required', 'string'],
            'currency' => ['required', 'string'],
        ]);

        $master = $this->repository->findByRegionAndCurrency(
            $request->input('travel_region'),
            $request->input('currency')
        );

        if (! $master) {
            return response()->json([
                'pocket_money_limit' => null,
                'meal_allowance_limit' => null,
            ]);
        }

        return response()->json([
            'pocket_money_limit' => (float) $master->pocket_money_limit,
            'meal_allowance_limit' => (float) $master->meal_allowance_limit,
        ]);
    }
}