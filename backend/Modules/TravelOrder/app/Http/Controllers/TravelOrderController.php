<?php

namespace Modules\TravelOrder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\{JsonResponse, Request, Response};
use Modules\TravelOrder\Http\Requests\StoreTravelOrderRequest;
use Modules\TravelOrder\Models\TravelOrder;
use Modules\TravelOrder\Services\{TravelOrderBookingService, TravelOrderService};

class TravelOrderController extends Controller
{
    public function __construct(private TravelOrderService $service, private TravelOrderBookingService $bookingService) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->list($request->user(), $request->only(['search', 'status', 'travel_region']), (int) $request->input('per_page', 10)));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->service->find($id, $request->user())]);
    }

    public function pdf(Request $request, int $id): Response
    {
        return $this->service->pdf($id, $request->user())->stream("TO-{$id}.pdf");
    }

    public function history(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->service->history($id, $request->user())]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $order = $this->service->approve($id, $request->user(), $request->input('note'));

        return response()->json(['message' => 'Travel Order has been successfully approved.', 'data' => $order]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate(['remark' => ['required', 'string']]);
        $this->service->reject($id, $request->user(), $request->input('remark'));

        return response()->json(['message' => 'Travel Order has been rejected.']);
    }

    public function revision(Request $request, int $id): JsonResponse
    {
        $v = $request->validate(['remark' => ['required', 'string', 'max:1000'], 'evidence' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120']]);
        $this->service->revisionGmo($id, $request->user(), $v['remark'], $request->file('evidence'));

        return response()->json(['message' => 'Travel Order has been sent for revision.']);
    }

    public function departmentLock(Request $request): JsonResponse
    {
        return response()->json($this->service->getDepartmentLockInfo($request->user()));
    }

    public function store(StoreTravelOrderRequest $request): JsonResponse
    {
        $order = $this->service->create($request->validated(), $request->user());
        $message = $order->status === 'draft' ? 'Travel Order has been saved as draft.' : 'Travel Order has been successfully submitted.';

        return response()->json(['message' => $message, 'data' => $order], 201);
    }

    public function update(StoreTravelOrderRequest $request, int $id): JsonResponse
    {
        $order = $this->service->update($id, $request->validated(), $request->user());
        $message = $order->status === 'draft' ? 'Travel Order draft has been updated.' : 'Travel Order has been successfully submitted.';

        return response()->json(['message' => $message, 'data' => $order]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $v = $request->validate(['remark' => ['required', 'string', 'max:1000'], 'evidence' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120']]);
        $order = $this->service->cancel($id, $request->user(), $v['remark'], $request->file('evidence'));

        return response()->json(['message' => 'Travel Order has been cancelled successfully.', 'data' => $order]);
    }

    public function completeGmoProcessing(Request $request, int $id): JsonResponse
    {
        $order = TravelOrder::with(['user', 'department', 'booking'])->findOrFail($id);
        $order = $this->bookingService->completeGmoProcessing($order, $request->user(), $request->all(), $request->file('e_ticket'), $request->file('hotel_reservation'));

        return response()->json(['message' => 'GMO processing has been completed successfully.', 'data' => $order->refresh()->load(['user', 'department', 'booking'])]);
    }

    public function updateGmoSchedule(Request $request, int $id): JsonResponse
    {
        $updated = $this->bookingService->updateSchedule(TravelOrder::findOrFail($id), $request->user(), $request->all());

        return response()->json(['message' => 'Travel Order schedule has been updated successfully.', 'data' => $updated->refresh()->load(['user', 'department', 'booking'])]);
    }

    public function updateGmoBookingItems(Request $request, int $id): JsonResponse
    {
        $updated = $this->bookingService->updateBookingItems(TravelOrder::findOrFail($id), $request->user(), $request->all(), $request->file('e_ticket'), $request->file('hotel_reservation'));

        return response()->json(['message' => 'Booking items have been updated successfully.', 'data' => $updated->refresh()->load(['user', 'department', 'booking'])]);
    }

    public function cancelGmoBookingItem(Request $request, int $id, string $itemType): JsonResponse
    {
        abort_unless(in_array($itemType, ['ticket', 'accommodation'], true), 404);
        $cancellation = $this->bookingService->cancelBookingItem(TravelOrder::findOrFail($id), $request->user(), $itemType, $request->all(), $request->file('confirmation_file'));

        return response()->json(['message' => ucfirst($itemType) . ' cancellation has been recorded successfully.', 'data' => $cancellation]);
    }

    public function updateGmoRefund(Request $request, int $id, string $itemType): JsonResponse
    {
        abort_unless(in_array($itemType, ['ticket', 'accommodation'], true), 404);
        $cancellation = $this->bookingService->updateCancellationRefund(TravelOrder::findOrFail($id), $request->user(), $itemType, $request->all(), $request->file('confirmation_file'));

        return response()->json(['message' => ucfirst($itemType) . ' refund has been updated successfully.', 'data' => $cancellation]);
    }

    public function resendGmoBookingEmail(Request $request, int $id): JsonResponse
    {
        $email = $this->bookingService->resendBookingEmail(TravelOrder::findOrFail($id), $request->user(), $request->all());

        return response()->json(['message' => 'Booking email has been resent successfully.', 'data' => $email]);
    }
}