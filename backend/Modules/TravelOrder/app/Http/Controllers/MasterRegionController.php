<?php

declare(strict_types=1);

namespace Modules\TravelOrder\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\TravelOrder\Http\Requests\StoreMasterRegionRequest;
use Modules\TravelOrder\Http\Requests\UpdateMasterRegionRequest;
use Modules\TravelOrder\Http\Resources\MasterRegionResource;
use Modules\TravelOrder\Services\MasterRegionService;

class MasterRegionController extends Controller
{
    public function __construct(
        private MasterRegionService $masterRegionService
    ) {
    }

    public function index(): JsonResponse
    {
        return ApiResponse::success(
            MasterRegionResource::collection($this->masterRegionService->getAll())
        );
    }

    public function show(int $id): JsonResponse
    {
        return ApiResponse::success(
            new MasterRegionResource($this->masterRegionService->findById($id))
        );
    }

    public function store(StoreMasterRegionRequest $request): JsonResponse
    {
        $region = $this->masterRegionService->create($request->validated());

        return ApiResponse::success(
            new MasterRegionResource($region),
            'Master region created successfully.',
            201
        );
    }

    public function update(UpdateMasterRegionRequest $request, int $id): JsonResponse
    {
        $region = $this->masterRegionService->update($id, $request->validated());

        return ApiResponse::success(
            new MasterRegionResource($region),
            'Master region updated successfully.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->masterRegionService->findById($id);

        if ($this->masterRegionService->hasRates($id)) {
            return ApiResponse::error(
                'Region masih memiliki data tarif sehingga tidak dapat dihapus.',
                422
            );
        }

        $this->masterRegionService->delete($id);

        return ApiResponse::success(null, 'Master region deleted successfully.');
    }
}