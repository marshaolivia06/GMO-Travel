<?php

namespace Modules\TravelOrder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\TravelOrder\Http\Requests\AddGroupMembersRequest;
use Modules\TravelOrder\Http\Requests\StoreTravelOrderGroupRequest;
use Modules\TravelOrder\Http\Requests\UpdateTravelOrderGroupRequest;
use Modules\TravelOrder\Services\TravelOrderGroupService;
use Modules\UserManagement\Models\User;

class TravelOrderGroupController extends Controller
{
    public function __construct(private TravelOrderGroupService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->list($request->only(['search', 'status']), (int) $request->input('per_page', 10)));
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => $this->service->show($id)]);
    }

    public function my(Request $request): JsonResponse
    {
    return response()->json(['data' => $this->service->myGroups($request->user()->id)]);
    }

    public function store(StoreTravelOrderGroupRequest $request): JsonResponse
    {
        $group = $this->service->create($request->validated());
        return response()->json(['message' => 'Group Trip has been created successfully.', 'data' => $group], 201);
    }

    public function update(UpdateTravelOrderGroupRequest $request, int $id): JsonResponse
    {
        $group = $this->service->update($id, $request->validated());
        return response()->json(['message' => 'Group Trip has been updated successfully.', 'data' => $group]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(['message' => 'Group Trip has been deleted successfully.']);
    }

    public function open(int $id): JsonResponse
    {
        $group = $this->service->open($id);
        return response()->json(['message' => 'Group Trip is already open.', 'data' => $group]);
    }

    public function close(int $id): JsonResponse
    {
        $group = $this->service->close($id);
        return response()->json(['message' => 'Group Trip has been closed successfully.', 'data' => $group]);
    }

    public function addMembers(AddGroupMembersRequest $request, int $id): JsonResponse
    {
        $group = $this->service->addMembers($id, $request->validated()['user_ids']);
        return response()->json(['message' => 'Members have been added successfully.', 'data' => $group]);
    }

    public function removeMember(int $id, int $userId): JsonResponse
    {
        $group = $this->service->removeMember($id, $userId);
        return response()->json(['message' => 'Member has been removed successfully.', 'data' => $group]);
    }

    public function users(): JsonResponse
    {
        $users = User::query()->with('department:id,name')->orderBy('name')->get(['id', 'name', 'department_id']);
        return response()->json(['data' => $users]);
    }
}
