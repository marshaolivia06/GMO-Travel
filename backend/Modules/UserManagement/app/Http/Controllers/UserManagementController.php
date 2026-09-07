<?php

namespace Modules\UserManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\User;

class UserManagementController extends Controller
{
    public function usersIndex()
    {
        return response()->json(
            User::with('roles.permissions')
                ->latest()
                ->get()
        );
    }

    public function usersStore(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'email:filter',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
            ],
            'role' => [
                'required',
                'string',
                'exists:roles,name',
            ],
        ]);

        $role = Role::where('name', $validated['role'])
            ->where('guard_name', 'web')
            ->firstOrFail();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make(
                $validated['password']
            ),
        ]);

        $user->assignRole($role);

        return response()->json([
            'message' => 'User created successfully.',
            'data' => $user
                ->fresh()
                ->load('roles.permissions'),
        ], 201);
    }

    public function usersShow(User $user)
    {
        return response()->json(
            $user->load('roles.permissions')
        );
    }

    public function usersUpdate(
        Request $request,
        User $user
    ) {
        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email:filter',
                'max:255',
                'unique:users,email,' . $user->id,
            ],
            'password' => [
                'sometimes',
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
            'role' => [
                'sometimes',
                'required',
                'string',
                'exists:roles,name',
            ],
        ]);

        $userData = [];

        if (isset($validated['name'])) {
            $userData['name'] = $validated['name'];
        }

        if (isset($validated['email'])) {
            $userData['email'] = $validated['email'];
        }

        if (!empty($validated['password'])) {
            $userData['password'] = Hash::make(
                $validated['password']
            );
        }

        if (!empty($userData)) {
            $user->update($userData);
        }

        if (isset($validated['role'])) {
            $role = Role::where(
                'name',
                $validated['role']
            )
                ->where('guard_name', 'web')
                ->firstOrFail();

            $user->syncRoles([$role]);
        }

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => $user
                ->fresh()
                ->load('roles.permissions'),
        ]);
    }

    public function usersDestroy(User $user)
    {
        $user->roles()->detach();

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    public function rolesIndex()
    {
        return response()->json(
            Role::where('guard_name', 'web')
                ->with('permissions')
                ->latest()
                ->get()
        );
    }

    public function rolesStore(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:roles,name',
            ],
            'permissions' => [
                'sometimes',
                'array',
            ],
            'permissions.*' => [
                'integer',
                'exists:permissions,id',
            ],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        if (isset($validated['permissions'])) {
            $permissions = Permission::whereIn(
                'id',
                $validated['permissions']
            )
                ->where('guard_name', 'web')
                ->get();

            $role->syncPermissions($permissions);
        }

        return response()->json([
            'message' => 'Role created successfully.',
            'data' => $role->load('permissions'),
        ], 201);
    }

    public function rolesShow(Role $role)
    {
        return response()->json(
            $role->load('permissions')
        );
    }

    public function rolesUpdate(
        Request $request,
        Role $role
    ) {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->where(function ($query) {
                        return $query->where('guard_name', 'web');
                    })
                    ->ignore($role->id),
            ],
            'permissions' => [
                'sometimes',
                'array',
            ],
            'permissions.*' => [
                'integer',
                'exists:permissions,id',
            ],
        ]);
    
        $role->update([
            'name' => trim($validated['name']),
            'guard_name' => 'web',
        ]);
    
        if (array_key_exists('permissions', $validated)) {
            $permissions = Permission::whereIn(
                'id',
                $validated['permissions']
            )
                ->where('guard_name', 'web')
                ->get();
    
            $role->syncPermissions($permissions);
        }
    
        return response()->json([
            'message' => 'Role updated successfully.',
            'data' => $role
                ->fresh()
                ->load('permissions'),
        ]);
    }    

    public function rolesDestroy(Role $role)
    {
        $role->users()->detach();

        $role->permissions()->detach();

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully.',
        ]);
    }

    public function permissionsIndex()
    {
        return response()->json(
            Permission::where('guard_name', 'web')
                ->latest()
                ->get()
        );
    }

    public function permissionsStore(Request $request)
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

        $permission = Permission::create([
            'name' => $validated['name'],
            'module' => $validated['module'],
            'guard_name' => 'web',
        ]);

        return response()->json([
            'message' => 'Permission created successfully.',
            'data' => $permission,
        ], 201);
    }

    public function permissionsShow(
        Permission $permission
    ) {
        return response()->json($permission);
    }

    public function permissionsSync(Request $request)
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
                'in:view,create,update,delete',
            ],
        ]);
    
        $oldModule = trim($validated['old_module']);
        $newModule = trim($validated['module']);
    
        $actions = array_values(
            array_unique($validated['actions'])
        );
    
        return DB::transaction(function () use (
            $oldModule,
            $newModule,
            $actions
        ) {
            $moduleName = strtolower(
                preg_replace('/\s+/', '-', $newModule)
            );
    
            $permissions = Permission::where('module', $oldModule)
                ->where('guard_name', 'web')
                ->get();
    
            if ($permissions->isEmpty()) {
                return response()->json([
                    'message' => 'Permission tidak ditemukan.',
                ], 404);
            }
    
            $existingByAction = [];
    
            foreach ($permissions as $permission) {
                $action = strtolower(
                    last(explode('.', $permission->name))
                );
    
                $existingByAction[$action] = $permission;
            }
    
            foreach ($actions as $action) {
                if (isset($existingByAction[$action])) {
                    $permission = $existingByAction[$action];
    
                    $permission->update([
                        'name' => "{$moduleName}.{$action}",
                        'module' => $newModule,
                        'guard_name' => 'web',
                    ]);
                } else {
                    Permission::create([
                        'name' => "{$moduleName}.{$action}",
                        'module' => $newModule,
                        'guard_name' => 'web',
                    ]);
                }
            }
    
            foreach ($existingByAction as $action => $permission) {
                if (!in_array($action, $actions)) {
                    $permission->roles()->detach();
                    $permission->delete();
                }
            }
    
            return response()->json([
                'message' => 'Permission updated successfully.',
                'data' => Permission::where('module', $newModule)
                    ->where('guard_name', 'web')
                    ->get(),
            ]);
        });
    }    

    public function permissionsUpdate(
        Request $request,
        Permission $permission
    ) {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:permissions,name,' .
                    $permission->id,
            ],
            'module' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $permission->update([
            'name' => $validated['name'],
            'module' => $validated['module'],
            'guard_name' => 'web',
        ]);

        return response()->json([
            'message' =>
                'Permission updated successfully.',
            'data' => $permission->fresh(),
        ]);
    }

    public function permissionsDestroy(
        Permission $permission
    ) {
        $permission
            ->roles()
            ->detach();

        $permission->delete();

        return response()->json([
            'message' =>
                'Permission deleted successfully.',
        ]);
    }
}