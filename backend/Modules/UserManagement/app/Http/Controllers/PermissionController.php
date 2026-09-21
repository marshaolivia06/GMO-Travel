<?php

declare(strict_types=1);

namespace Modules\UserManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\UserManagement\Repositories\PermissionRepository;

class PermissionController extends Controller
{
    public function __construct(
        private PermissionRepository $permissionRepository
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
            $this->permissionRepository->syncPermissions($validated)
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

        $permission = $this->permissionRepository->update(
            $id,
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
        $this->permissionRepository->detachRoles($id);
        $this->permissionRepository->delete($id);

        return response()->json([
            'message' => 'Permission deleted successfully.',
        ]);
    }
}
