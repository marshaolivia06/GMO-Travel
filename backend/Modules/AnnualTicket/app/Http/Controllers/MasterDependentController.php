<?php

namespace Modules\AnnualTicket\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AnnualTicket\Http\Requests\MasterDependentRequest;
use Modules\AnnualTicket\Services\MasterDependentService;
use Modules\AnnualTicket\Http\Requests\UpdateMasterDependentRequest;

class MasterDependentController extends Controller
{
    public function __construct(private MasterDependentService $service) {}

    public function index(): JsonResponse
    {
    return response()->json($this->service->listGrouped());
    }

    public function myDependents(Request $request): JsonResponse
{
    return response()->json($this->service->travelersFor($request->user()));
}

    public function employeeOptions(): JsonResponse
    {
    return response()->json($this->service->availableEmployees());
    }

    public function store(MasterDependentRequest $request): JsonResponse
    {
    $this->service->createForEmployee($request->validated());

    return response()->json(['message' => 'Dependents added'], 201);
    }

    public function update(UpdateMasterDependentRequest $request, int $employeeId): JsonResponse
    {
    $this->service->syncForEmployee($employeeId, $request->validated()['dependents']);

    return response()->json(['message' => 'Dependents updated']);
    }

    public function destroy(int $employeeId): JsonResponse
    {
    $this->service->deleteForEmployee($employeeId);

    return response()->json(['message' => 'Dependents deleted']);
    }
}