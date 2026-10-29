<?php

namespace Modules\AnnualTicket\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\AnnualTicket\Http\Requests\MasterDestinationRequest;
use Modules\AnnualTicket\Http\Requests\UpdateMasterDestinationRequest;
use Modules\AnnualTicket\Services\MasterDestinationService;

class MasterDestinationController extends Controller
{
    public function __construct(private MasterDestinationService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->list());
    }

    public function store(MasterDestinationRequest $request): JsonResponse
    {
        $this->service->create($request->validated());

        return response()->json(['message' => 'Destination added'], 201);
    }

    public function update(UpdateMasterDestinationRequest $request, int $id): JsonResponse
    {
        $this->service->update($id, $request->validated());

        return response()->json(['message' => 'Destination updated']);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(['message' => 'Destination deleted']);
    }
}