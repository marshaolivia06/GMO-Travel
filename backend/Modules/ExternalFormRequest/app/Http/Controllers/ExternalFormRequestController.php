<?php

namespace Modules\ExternalFormRequest\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\ExternalFormRequest\Http\Requests\StoreExternalFormRequest;
use Modules\ExternalFormRequest\Models\ExternalFormRequest;
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

        return response()->json([
            'message' => $form->status === 'draft'
                ? 'External form request saved as draft.'
                : 'External form request submitted successfully.',
            'data' => $form->load('travelers'),
        ], 201);
    }

    public function history(Request $request, int $id): JsonResponse
    {
        return response()->json([
            'data' => $this->service->history($request->user(), $id),
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        return $this->respondDecision($this->service->approve($request->user(), $id));
    }

    public function revision(Request $request, int $id): JsonResponse
    {
        return $this->respondDecision($this->service->revision($request->user(), $id, $request->input('remark')));
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        return $this->respondDecision($this->service->reject($request->user(), $id, $request->input('remark')));
    }

    private function respondDecision(ExternalFormRequest $form): JsonResponse
    {
        $last = $form->approvals->where('status', '!=', 'pending')->sortByDesc('step')->first();

        $message = match ($last?->status) {
            'revision' => 'External form request returned for revision.',
            'rejected' => 'External form request rejected.',
            'reviewed' => 'External form request reviewed successfully.',
            default => 'External form request approved successfully.',
        };

        return response()->json([
            'message' => $message,
            'data' => $form->load('travelers'),
        ]);
    }

    public function pdf(Request $request, int $id): Response|ResponseFactory
    {
        return $this->service->pdf($id, $request->user())->stream("EF-{$id}.pdf");
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
