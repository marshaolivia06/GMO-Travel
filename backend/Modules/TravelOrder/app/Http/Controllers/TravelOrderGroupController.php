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
use Modules\MasterManagement\Models\Department;
use Modules\MasterManagement\Models\Section;

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
        $users = User::query()
            ->with('roles:id,name')
            ->where('status', 1)
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'Admin'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $userIds = $users->pluck('id');

        $sections = Section::query()
            ->with('department:id,name')
            ->where('status', 1)
            ->whereIn('section_head_id', $userIds)
            ->get()
            ->keyBy('section_head_id');

        $departments = Department::query()
            ->where('status', 1)
            ->whereIn('dept_head_id', $userIds)
            ->get()
            ->keyBy('dept_head_id');

        $data = $users->map(function (User $user) use ($sections, $departments) {
            $roleNames = $user->roles->pluck('name');
            $section = $sections->get($user->id);
            $department = $departments->get($user->id);

            if ($roleNames->contains('Sect Head') && $section) {
                $sectionName = $section->name;
                $departmentName = $section->department?->name;
                $departmentId = $section->department_id;
            } else {
                $sectionName = null;
                $departmentName = $department?->name;
                $departmentId = $department?->id;
            }

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $roleNames,
                'section_name' => $sectionName,
                'department_id' => $departmentId,
                'department_name' => $departmentName,
            ];
        });

        return response()->json(['data' => $data]);
    }
}
