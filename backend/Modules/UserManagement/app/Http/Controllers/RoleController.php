<?php

declare(strict_types=1);

namespace Modules\UserManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\UserManagement\Repositories\RoleRepository;

class RoleController extends Controller
{
    public function __construct(
        private RoleRepository $roleRepository
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
            'unique:roles,name',
        ],
        'guard_name' => [
            'nullable',
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

    $role = $this->roleRepository->create([
        'name' => $data['name'],
        'guard_name' => $data['guard_name'] ?? 'web',
        'status' => 1,
        'permissions' => $data['permissions'] ?? [],
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
                'unique:roles,name,' . $id,
            ],
            'permissions' => [
                'sometimes',
                'array',
            ],
            'permissions.*' => [
                'integer',
            ],
        ]);

        $role = $this->roleRepository->update($id, $data);

        return response()->json($role);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->roleRepository->delete($id);

        return response()->json([
            'message' => 'Role deleted successfully.',
        ]);
    }
}
