<?php

declare(strict_types=1);

namespace Modules\TravelOrder\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Modules\TravelOrder\Http\Requests\StoreTravelAdvanceMasterRequest;
use Modules\TravelOrder\Http\Requests\UpdateTravelAdvanceMasterRequest;
use Modules\TravelOrder\Http\Resources\TravelAdvanceMasterResource;
use Modules\TravelOrder\Services\TravelAdvanceService;

class TravelAdvanceMasterController extends Controller
{
    public function __construct(
        private TravelAdvanceService $travelAdvanceService
    ) {
    }

    public function index(): JsonResponse
    {
        $travelAdvances = $this->travelAdvanceService->getAll();

        return ApiResponse::success(
            TravelAdvanceMasterResource::collection($travelAdvances)
        );
    }

    public function show(int $id): JsonResponse
    {
        return ApiResponse::success(
            new TravelAdvanceMasterResource(
                $this->travelAdvanceService->findById($id)
            )
        );
    }

    public function limitByGrade(HttpRequest $request): JsonResponse
    {
        $request->validate([
            'travel_region' => ['required', 'string'],
            'grade' => ['required', 'integer'],
        ]);

        $travelAdvance = $this->travelAdvanceService->findByRegionAndGrade(
            $request->input('travel_region'),
            (int) $request->input('grade')
        );

        if (! $travelAdvance) {
            return ApiResponse::success([
                'pocket_money_limit' => null,
                'meal_allowance_limit' => null,
            ]);
        }

        return ApiResponse::success([
            'pocket_money_limit' => (float) $travelAdvance->pocket_money_limit,
            'meal_allowance_limit' => (float) $travelAdvance->meal_allowance_limit,
        ]);
    }

    public function regions(): JsonResponse
    {
        return ApiResponse::success(
            $this->travelAdvanceService->getDistinctRegions()
        );
    }

    public function countries(HttpRequest $request): JsonResponse
    {
        $request->validate([
            'travel_region' => ['required', 'string'],
        ]);

        $countries = $this->travelAdvanceService->getCountriesByRegion(
            $request->travel_region
        );

        return ApiResponse::success($countries);
    }

    public function store(
        StoreTravelAdvanceMasterRequest $request
    ): JsonResponse {
        $travelAdvance = $this->travelAdvanceService->create(
            $request->validated()
        );

        return ApiResponse::success(
            new TravelAdvanceMasterResource($travelAdvance),
            'Travel Advance master created successfully.',
            201
        );
    }

    public function update(
        UpdateTravelAdvanceMasterRequest $request,
        int $id
    ): JsonResponse {
        $travelAdvance = $this->travelAdvanceService->update(
            $id,
            $request->validated()
        );

        return ApiResponse::success(
            new TravelAdvanceMasterResource($travelAdvance),
            'Travel Advance master updated successfully.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->travelAdvanceService->findById($id);
        $this->travelAdvanceService->delete($id);

        return ApiResponse::success(
            null,
            'Travel Advance master deleted successfully.'
        );
    }

    public function limit(HttpRequest $request): JsonResponse
    {
        $request->validate([
            'travel_region' => ['required', 'string'],
            'currency' => ['required', 'string'],
        ]);

        $travelRegion = $request->input('travel_region');
        $currency = $request->input('currency');

        if ($travelRegion === 'Domestic') {
            $user = $request->user();

            if (! $user || $user->grade === null) {
                return ApiResponse::error(
                    'Grade user tidak ditemukan.',
                    422
                );
            }

            $travelAdvance = $this->travelAdvanceService->findByRegionAndGrade(
                'Domestic',
                (int) $user->grade
            );
        } else {
            $travelAdvance = $this->travelAdvanceService->findByRegionAndCurrency(
                $travelRegion,
                $currency
            );
        }

        if (! $travelAdvance) {
            return ApiResponse::success([
                'pocket_money_limit' => null,
                'meal_allowance_limit' => null,
            ]);
        }

        return ApiResponse::success([
            'pocket_money_limit' => (float) $travelAdvance->pocket_money_limit,
            'meal_allowance_limit' => (float) $travelAdvance->meal_allowance_limit,
        ]);
    }
}