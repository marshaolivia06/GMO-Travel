<?php

namespace Modules\TravelOrder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\TravelOrder\Http\Requests\StoreTravelOrderRequest;
use Modules\TravelOrder\Services\TravelOrderService;

class TravelOrderController extends Controller
{
    public function __construct(
        private TravelOrderService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $this->service->list(
            $request->only(['search', 'status', 'travel_region']),
            (int) $request->input('per_page', 10)
        );

        return response()->json($orders);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'data' => $this->service->find($id),
        ]);
    }

    public function departmentLock(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->getDepartmentLockInfo($request->user())
        );
    }

    public function store(StoreTravelOrderRequest $request): JsonResponse
    {
        $order = $this->service->create($request->validated(), $request->user());

        return response()->json([
            'message' => 'The travel order has been successfully submitted.',
            'data' => $order,
        ], 201);
    }
}