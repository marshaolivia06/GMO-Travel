<?php

namespace Modules\TravelExpenseReport\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\TravelExpenseReport\Http\Requests\StoreTravelExpenseReportRequest;
use Modules\TravelExpenseReport\Services\TravelExpenseReportService;

class TravelExpenseReportController extends Controller
{
    public function __construct(private TravelExpenseReportService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->listFor($request->user())
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json(
            $this->service->findFor($request->user(), $id)
        );
    }

    public function history(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->service->historyById($id, $request->user())]);
    }

    public function pdf(Request $request, int $id): Response|ResponseFactory
    {
        return $this->service->pdf($id, $request->user())->stream("TER-{$id}.pdf");
    }

    public function store(StoreTravelExpenseReportRequest $request): JsonResponse
    {
        $report = $this->service->create(
            $request->user(),
            $request->validated(),
            $request->file('attachments', []),
            $request->file('proof_of_payment'),
        );

        return response()->json([
            'message' => $report->status === 'submitted'
                ? 'Travel Expense Report has been submitted successfully.'
                : 'Travel Expense Report draft has been saved successfully.',
            'data' => $report,
        ], 201);
    }

    // TER berbasis Travel Order (business trip individu & grup): rincian per hari dikirim sebagai JSON + file struk per item
    public function storeTravelOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'travel_order_id' => ['required', 'integer', 'exists:travel_orders,id'],
            'action' => ['sometimes', 'in:draft,submit'],
            'days' => ['required', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $days = json_decode($validated['days'], true);

        abort_if(! is_array($days), 422, 'The days payload is invalid.');

        $report = $this->service->storeTravelOrderReport(
            $request->user(),
            ['travel_order_id' => $validated['travel_order_id'], 'action' => $validated['action'] ?? 'draft'],
            $days,
            $request->file('attachments', []),
        );

        return response()->json([
            'message' => $report->status === 'submitted'
                ? 'Travel Expense Report has been submitted successfully.'
                : 'Travel Expense Report draft has been saved successfully.',
            'data' => $report->load(['days', 'expenses']),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'total_actual_amount' => ['nullable', 'numeric', 'min:0'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'proof_of_payment' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'remove_proof_of_payment' => ['nullable', 'boolean'],
        ]);

        $report = $this->service->updateDraft(
            $request->user(),
            $id,
            $request->all(),
            $request->file('attachments', []),
            $request->file('proof_of_payment'),
            $request->boolean('remove_proof_of_payment'),
        );

        return response()->json([
            'message' => 'Travel Expense Report draft has been updated successfully.',
            'data' => $report,
        ]);
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        $report = $this->service->submit($request->user(), $id);

        return response()->json([
            'message' => 'Travel Expense Report has been submitted successfully.',
            'data' => $report,
        ]);
    }

    public function check(Request $request, int $id): JsonResponse
    {
        $this->service->check($request->user(), $id, $request->input('note'));

        return response()->json([
            'message' => 'Travel Expense Report has been checked successfully.',
            'data' => $this->service->findById($id),
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $this->service->approve($request->user(), $id, $request->input('note'));

        return response()->json([
            'message' => 'Travel Expense Report has been approved successfully.',
            'data' => $this->service->findById($id),
        ]);
    }

    public function revision(Request $request, int $id): JsonResponse
    {
        $request->validate(['remark' => ['required', 'string']]);

        $this->service->revision($request->user(), $id, $request->input('remark'));

        return response()->json([
            'message' => 'Travel Expense Report has been returned for revision.',
            'data' => $this->service->findById($id),
        ]);
    }

    public function destroy(Request $request, int $id): Response|ResponseFactory
    {
        $this->service->deleteDraft($request->user(), $id);

        return response()->noContent();
    }

    public function deleteAttachment(Request $request, int $id, int $attachmentId): JsonResponse
    {
        $this->service->deleteAttachment($request->user(), $id, $attachmentId);

        return response()->json(['message' => 'Attachment has been removed successfully.']);
    }
}
