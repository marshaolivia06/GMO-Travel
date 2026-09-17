<?php

namespace Modules\UserManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\UserManagement\Repositories\PermissionRepository;
use Modules\UserManagement\Services\PermissionService;

class PermissionController extends Controller
{
    public function __construct(
        private PermissionRepository $permissionRepository,
        private PermissionService $permissionService
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json(
            $this->permissionRepository->getAll()
        );
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(
            $this->permissionRepository->findById($id)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:permissions,name',
            ],
            'module' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $permission = $this->permissionRepository->create([
            'name' => $validated['name'],
            'module' => $validated['module'],
            'guard_name' => 'web',
            'status' => 1,
        ]);

        return response()->json([
            'message' => 'Permission created successfully.',
            'data' => $permission,
        ], 201);
    }

    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'old_module' => [
                'required',
                'string',
                'max:255',
            ],
            'module' => [
                'required',
                'string',
                'max:255',
            ],
            'actions' => [
                'required',
                'array',
                'min:1',
            ],
            'actions.*' => [
                'required',
                'string',
                'in:view,create,update,delete,approve',
            ],
        ]);

        return response()->json(
            $this->permissionService->syncPermissions($validated)
        );
    }

    public function update(
        Request $request,
        int $id
    ): JsonResponse {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:permissions,name,' . $id,
            ],
            'module' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $permission = $this->permissionRepository->findById($id);

        $permission = $this->permissionRepository->update(
            $permission,
            [
                'name' => $validated['name'],
                'module' => $validated['module'],
                'guard_name' => 'web',
            ]
        );

        return response()->json([
            'message' => 'Permission updated successfully.',
            'data' => $permission,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $permission = $this->permissionRepository->findById($id);

        $this->permissionRepository->detachRoles($permission);
        $this->permissionRepository->delete($permission);

        return response()->json([
            'message' => 'Permission deleted successfully.',
        ]);
    }
}