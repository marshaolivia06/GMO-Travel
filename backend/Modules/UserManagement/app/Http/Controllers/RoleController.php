<?php

namespace Modules\UserManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\UserManagement\Repositories\RoleRepository;
use Modules\UserManagement\Services\RoleService;

class RoleController extends Controller
{
    public function __construct(
        private RoleRepository $roleRepository,
        private RoleService $roleService
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json(
            $this->roleRepository->getAll()
        );
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(
            $this->roleRepository->findById($id)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'guard_name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $role = $this->roleRepository->create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
        ]);

        return response()->json($role, 201);
    }

    public function update(
        Request $request,
        int $id
    ): JsonResponse {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'permissions' => [
                'sometimes',
                'array',
            ],
            'permissions.*' => [
                'integer',
            ],
        ]);

        $role = $this->roleService->updateRole(
            $id,
            $data
        );

        return response()->json($role);
    }

    public function destroy(int $id): JsonResponse
    {
        $role = $this->roleRepository->findById($id);

        $this->roleRepository->delete($role);

        return response()->json([
            'message' => 'Role deleted successfully.',
        ]);
    }
}
