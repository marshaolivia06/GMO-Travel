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

class MasterRateController extends Controller
{
    public function __construct(
        private MasterRateService $masterRateService
    ) {
    }

    public function index(HttpRequest $request): JsonResponse
    {
        $regionId = $request->filled('region_id') ? $request->integer('region_id') : null;

        return ApiResponse::success(
            MasterRateResource::collection($this->masterRateService->getAll($regionId))
        );
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