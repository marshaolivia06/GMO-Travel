<?php

declare(strict_types=1);

namespace Modules\TravelOrder\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Modules\TravelOrder\Http\Requests\StoreMasterRateRequest;
use Modules\TravelOrder\Http\Requests\UpdateMasterRateRequest;
use Modules\TravelOrder\Http\Resources\MasterRateResource;
use Modules\TravelOrder\Services\MasterRateService;
use Carbon\Carbon;
use Modules\TravelOrder\Services\MasterRateLimitService;

class MasterRateController extends Controller
{
    public function __construct(
        private MasterRateService $masterRateService,
        private MasterRateLimitService $masterRateLimitService
    ) {
    }

    public function index(HttpRequest $request): JsonResponse
    {
        $regionId = $request->filled('region_id') ? $request->integer('region_id') : null;

        return ApiResponse::success(
            MasterRateResource::collection($this->masterRateService->getAll($regionId))
        );
    }

    public function limits(HttpRequest $request): JsonResponse
{
    $data = $request->validate(['region_id' => ['required', 'integer', 'exists:master_regions,id'], 'destination' => ['nullable', 'string', 'max:255'], 'departure_date' => ['required', 'date'], 'return_date' => ['required', 'date', 'after_or_equal:departure_date']]);
    $days = (int) Carbon::parse($data['departure_date'])->startOfDay()->diffInDays(Carbon::parse($data['return_date'])->startOfDay(), true) + 1;
    $limits = $this->masterRateLimitService->getLimits((int) $data['region_id'], $data['destination'] ?? null, (int) $request->user()->grade, $days, $data['departure_date']);

    abort_if(! $limits, 404, 'Master Rate data not found.');

    return ApiResponse::success($limits);
}

    public function show(int $id): JsonResponse
    {
        return ApiResponse::success(
            new MasterRateResource($this->masterRateService->findById($id))
        );
    }

    public function store(StoreMasterRateRequest $request): JsonResponse
    {
        $rate = $this->masterRateService->create($request->validated());

        return ApiResponse::success(
            new MasterRateResource($rate),
            'Master rate created successfully.',
            201
        );
    }

    public function update(UpdateMasterRateRequest $request, int $id): JsonResponse
    {
        $rate = $this->masterRateService->update($id, $request->validated());

        return ApiResponse::success(
            new MasterRateResource($rate),
            'Master rate updated successfully.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->masterRateService->findById($id);
        $this->masterRateService->delete($id);

        return ApiResponse::success(null, 'Master rate deleted successfully.');
    }
}