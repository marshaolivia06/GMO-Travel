<?php

namespace Modules\TravelOrder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
            $request->user(),
            $request->only(['search', 'status', 'travel_region']),
            (int) $request->input('per_page', 10)
        );

        return response()->json($orders);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json([
            'data' => $this->service->find($id, $request->user()),
        ]);
    }

    public function pdf(Request $request, int $id): Response
    {
    $pdf = $this->service->pdf($id, $request->user());
    return $pdf->stream("TO-{$id}.pdf");
    }

    public function approve(Request $request, int $id): JsonResponse
{
    $order = $this->service->approve(
        $id,
        $request->user(),
        $request->input('note')
    );

    return response()->json([
        'message' => 'Travel Order has been successfully approved.',
        'data' => $order,
    ]);
}

public function reject(Request $request, int $id): JsonResponse
{
    $request->validate([
        'remark' => ['required', 'string'],
    ]);

    $this->service->reject(
        $id,
        $request->user(),
        $request->input('remark')
    );

    return response()->json([
        'message' => 'Travel Order has been rejected.',
    ]);
}

public function revision(Request $request, int $id): JsonResponse
{
    $request->validate([
        'remark' => ['required', 'string'],
    ]);

    $this->service->revision(
        $id,
        $request->user(),
        $request->input('remark')
    );

    return response()->json([
        'message' => 'Travel Order has been sent for revision.',
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
        $order = $this->service->create(
            $request->validated(),
            $request->user()
        );

        $message = $order->status === 'draft'
            ? 'Travel Order has been saved as draft.'
            : 'Travel Order has been successfully submitted.';

        return response()->json([
            'message' => $message,
            'data' => $order,
        ], 201);
    }

    public function update(
        StoreTravelOrderRequest $request,
        int $id
    ): JsonResponse {
        $order = $this->service->update(
            $id,
            $request->validated(),
            $request->user()
        );

        $message = $order->status === 'draft'
            ? 'Travel Order draft has been updated.'
            : 'Travel Order has been successfully submitted.';

        return response()->json([
            'message' => $message,
            'data' => $order,
        ]);
    }
}