<?php

namespace Modules\AnnualTicket\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\AnnualTicket\Http\Requests\ProcessAnnualTicketRequest;
use Modules\AnnualTicket\Http\Requests\ResubmitAnnualTicketRequest;
use Modules\AnnualTicket\Http\Requests\StoreAnnualTicketRequest;
use Modules\AnnualTicket\Services\AnnualTicketService;

class AnnualTicketController extends Controller
{
    public function __construct(private AnnualTicketService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->listFor($request->user())
        );
    }

    public function quota(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->quotaFor($request->user())
        );
    }

    public function store(StoreAnnualTicketRequest $request): JsonResponse
    {
        $ticket = $this->service->create(
            $request->user(),
            $request->validated(),
            $request->file('evidence')
        );

        return response()->json([
            'message' => 'Annual ticket request submitted successfully.',
            'data' => $ticket->load('legs'),
        ], 201);
    }

    public function resubmit(ResubmitAnnualTicketRequest $request, int $id): JsonResponse
    {
        $ticket = $this->service->resubmit(
            $request->user(),
            $id,
            $request->validated(),
            $request->file('evidence')
        );

        return response()->json([
            'message' => 'Annual ticket has been resubmitted successfully.',
            'data' => $ticket->load('legs'),
        ]);
    }

    public function check(Request $request, int $id): JsonResponse
    {
        $ticket = $this->service->check($request->user(), $id, $request->input('note'));

        return response()->json(['message' => 'Annual Ticket has been checked successfully.', 'data' => $ticket]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $ticket = $this->service->approve($request->user(), $id, $request->input('note'));

        return response()->json(['message' => 'Annual Ticket has been approved successfully.', 'data' => $ticket]);
    }

    public function revision(Request $request, int $id): JsonResponse
    {
        $request->validate(['remark' => ['required', 'string', 'max:1000']]);
        $ticket = $this->service->revision($request->user(), $id, $request->input('remark'));

        return response()->json(['message' => 'Annual Ticket has been returned for revision.', 'data' => $ticket]);
    }

    public function process(ProcessAnnualTicketRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        $ticket = $this->service->processByGmo(
            $request->user(),
            $id,
            $request->input('note'),
            $request->input('fare_references', []),
            $request->file('attachments', []),
            [
                'currency' => $validated['booking_currency'] ?? null,
                'amount' => $validated['booking_amount'] ?? null,
            ],
            $request->file('e_ticket'),
            $validated['email'] ?? [],
        );

        return response()->json(['message' => 'Annual Ticket has been processed successfully.', 'data' => $ticket]);
    }

    public function updateSchedule(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reschedules' => ['required', 'array'],
            'reschedules.*.leg_id' => ['required', 'integer'],
            'reschedules.*.reschedule_date' => ['nullable', 'date'],
        ]);

        $ticket = $this->service->updateSchedule(
            $request->user(),
            $id,
            $request->input('reschedules', []),
        );

        return response()->json(['message' => 'Travel schedule has been updated successfully.', 'data' => $ticket]);
    }

    public function updateBookingDocuments(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $ticket = $this->service->updateBookingDocuments(
            $request->user(),
            $id,
            $request->file('documents', []),
        );

        return response()->json(['message' => 'Issued travel document has been updated successfully.', 'data' => $ticket]);
    }

    public function cancelLeg(Request $request, int $id, int $legId): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'refund_status' => ['required', 'in:Fully Refund,Partially Refunded,Pending Refund,No Refund'],
            // Harga asli satuan tiket diinput GMO saat pembatalan
            'original_amount' => ['required', 'numeric', 'min:1'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'non_refundable_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cancellation = $this->service->cancelLeg(
            $request->user(),
            $id,
            $legId,
            $request->all(),
            $request->file('confirmation'),
        );

        return response()->json(['message' => 'Ticket cancellation has been recorded successfully.', 'data' => $cancellation]);
    }

    public function updateLegRefund(Request $request, int $id, int $legId): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'refund_status' => ['required', 'in:Fully Refund,Partially Refunded,Pending Refund,No Refund'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'non_refundable_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cancellation = $this->service->updateLegCancellationRefund(
            $request->user(),
            $id,
            $legId,
            $request->all(),
            $request->file('confirmation'),
        );

        return response()->json(['message' => 'Ticket refund has been updated successfully.', 'data' => $cancellation]);
    }

    public function resendEmail(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'to' => ['required'],
            'cc' => ['nullable'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        $emailLog = $this->service->resendBookingEmail(
            $request->user(),
            $id,
            $request->all(),
        );

        return response()->json(['message' => 'Booking confirmation email has been resent successfully.', 'data' => $emailLog]);
    }

    public function cancelRequest(Request $request, int $id): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $ticket = $this->service->requestCancellation($request->user(), $id, $request->input('reason'));

        return response()->json(['message' => 'Cancellation request has been submitted successfully.', 'data' => $ticket]);
    }

    public function cancelFinalize(Request $request, int $id): JsonResponse
    {
        $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        $ticket = $this->service->cancelFinalize($request->user(), $id, $request->input('note'));

        return response()->json(['message' => 'Cancellation has been finalized successfully.', 'data' => $ticket]);
    }

    public function cancelReject(Request $request, int $id): JsonResponse
    {
        $request->validate(['remark' => ['required', 'string', 'max:1000']]);

        $ticket = $this->service->cancelReject($request->user(), $id, $request->input('remark'));

        return response()->json(['message' => 'Cancellation request has been rejected.', 'data' => $ticket]);
    }

    public function history(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->service->historyById($id, $request->user())]);
    }

    public function pdf(Request $request, int $id): Response|ResponseFactory
    {
        return $this->service->pdf($id, $request->user())->stream("AT-{$id}.pdf");
    }
}
