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
            'module' => $this->moduleRules(),
            'name' => [
                ...$this->nameRules($request),
                'unique:permissions,name',
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
            'module' => $this->moduleRules(),
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
            'module' => $this->moduleRules(),
            'name' => [
                ...$this->nameRules($request),
                'unique:permissions,name,' . $id,
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
        // 404 untuk permission yang tidak ada / dikelola sistem
        $this->permissionRepository->findById($id);

        $this->permissionRepository->detachRoles($id);
        $this->permissionRepository->delete($id);

        return response()->json([
            'message' => 'Permission deleted successfully.',
        ]);
    }

    private function moduleRules(): array
    {
        return [
            'required',
            'string',
            'max:100',
            'regex:/^[A-Za-z0-9]+([ -][A-Za-z0-9]+)*$/',
            function (string $attribute, mixed $value, \Closure $fail) {
                if (in_array(self::moduleSlug((string) $value), PermissionRepository::SYSTEM_MODULES, true)) {
                    $fail('This module name is reserved by the system.');
                }
            },
        ];
    }

    private function nameRules(Request $request): array
    {
        return [
            'required',
            'string',
            'max:255',
            'regex:/^[a-z0-9]+(-[a-z0-9]+)*\.(view|create|update|delete|approve)$/',
            function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                $prefix = self::moduleSlug((string) $request->input('module')) . '.';

                if (! str_starts_with((string) $value, $prefix)) {
                    $fail("The permission name must start with \"{$prefix}\".");
                }
            },
        ];
    }

    private static function moduleSlug(string $module): string
    {
        return strtolower((string) preg_replace('/\s+/', '-', trim($module)));
    }
}
