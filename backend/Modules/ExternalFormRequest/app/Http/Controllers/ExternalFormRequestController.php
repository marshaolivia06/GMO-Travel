<?php

namespace Modules\ExternalFormRequest\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ExternalFormRequest\Http\Requests\StoreExternalFormRequest;
use Modules\ExternalFormRequest\Services\ExternalFormRequestService;

class ExternalFormRequestController extends Controller
{
    public function __construct(private ExternalFormRequestService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->listFor($request->user())
        );
    }

    public function store(StoreExternalFormRequest $request): JsonResponse
    {
        $form = $this->service->create(
            $request->user(),
            $request->validated(),
            $request->input('travelers', []) ?? [],
        );
    }

    public function history(Request $request, int $id): JsonResponse
    {
        return response()->json([
            'data' => $this->service->history($request->user(), $id),
        ]);
    }

    public function update(StoreExternalFormRequest $request, int $id): JsonResponse
    {
        $form = $this->service->update(
            $request->user(),
            $id,
            $request->validated(),
            $request->input('travelers', []) ?? [],
        );

        return response()->json([
            'message' => $form->status === 'draft'
                ? 'External form request saved as draft.'
                : 'External form request submitted successfully.',
            'data' => $form->load('travelers'),
        ]);
    }
}
