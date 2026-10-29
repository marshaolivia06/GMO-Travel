<?php

namespace Modules\AnnualTicket\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    public function history(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->service->historyById($id, $request->user())]);
    }

    public function pdf(Request $request, int $id): Response|ResponseFactory
    {
        return $this->service->pdf($id, $request->user())->stream("AT-{$id}.pdf");
    }
}
