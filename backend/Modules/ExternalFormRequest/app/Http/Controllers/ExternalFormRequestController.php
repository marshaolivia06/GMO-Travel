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

    public function divHeads(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->divHeads()
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
        $data = $request->validate([
            'div_head_id' => ['nullable', 'integer', 'exists:users,id'],
            'operator' => ['nullable', 'string', 'max:255'],
            'flight_vessel_no' => ['nullable', 'string', 'max:255'],
            'departure_atd' => ['nullable', 'date'],
            'departure_ata' => ['nullable', 'date'],
            'return_operator' => ['nullable', 'string', 'max:255'],
            'return_flight_vessel_no' => ['nullable', 'string', 'max:255'],
            'return_atd' => ['nullable', 'date'],
            'return_ata' => ['nullable', 'date'],
            'hotel' => ['nullable', 'string', 'max:255'],
            'room_type' => ['nullable', 'string', 'max:255'],
            'remark' => ['nullable', 'string'],
        ]);

        return $this->respondDecision($this->service->approve($request->user(), $id, $data));
    }

    public function release(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'operator' => ['nullable', 'string', 'max:255'],
            'flight_vessel_no' => ['nullable', 'string', 'max:255'],
            'departure_atd' => ['nullable', 'date'],
            'departure_ata' => ['nullable', 'date'],
            'return_operator' => ['nullable', 'string', 'max:255'],
            'return_flight_vessel_no' => ['nullable', 'string', 'max:255'],
            'return_atd' => ['nullable', 'date'],
            'return_ata' => ['nullable', 'date'],
            'hotel' => ['nullable', 'string', 'max:255'],
            'room_type' => ['nullable', 'string', 'max:255'],
            'departure_booking_code' => ['nullable', 'string', 'max:255'],
            'return_booking_code' => ['nullable', 'string', 'max:255'],
            'reservation_confirmation_no' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'array'],
            'email.to' => ['required', 'string'],
            'email.cc' => ['nullable', 'string'],
            'email.subject' => ['required', 'string', 'max:255'],
            'email.message' => ['nullable', 'string'],
            'attachment' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $path = $request->file('attachment')?->store('external-form-releases', 'public');
        $originalName = $request->file('attachment')?->getClientOriginalName();

        $form = $this->service->release($request->user(), $id, $data, $path, $originalName);

        return response()->json([
            'message' => 'External form request released successfully.',
            'data' => $form->load('travelers', 'emails'),
        ]);
    }

    public function revision(Request $request, int $id): JsonResponse
    {
        return $this->respondDecision($this->service->revision($request->user(), $id, $request->input('remark')));
    }

    // Kirim ulang email release terakhir pada pengajuan ber-status released
    public function resend(Request $request, int $id): JsonResponse
    {
        $form = $this->service->resendEmail($request->user(), $id);

        return response()->json([
            'message' => 'Release email resent successfully.',
            'data' => $form->load('travelers', 'emails'),
        ]);
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
