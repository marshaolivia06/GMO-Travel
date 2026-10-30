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
        $ticket = $this->service->processByGmo(
            $request->user(),
            $id,
            $request->input('note'),
            $request->input('fare_references', []),
            $request->file('attachments', []),
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

    public function cancelRequest(Request $request, int $id): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $ticket = $this->service->requestCancellation($request->user(), $id, $request->input('reason'));

        return response()->json(['message' => 'Cancellation request has been submitted successfully.', 'data' => $ticket]);
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
