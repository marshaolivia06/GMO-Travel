<?php

namespace Modules\UserManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\User;

class UserManagementController extends Controller
{
    public function usersIndex()
    {
        return response()->json(
            User::with('roles.permissions')->latest()->get()
        );
    }

    public function usersStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email:filter',
                'max:255',
                'unique:users,email',
            ],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        return response()->json([
            'message' => 'User created successfully.',
            'data' => $user->load('roles'),
        ], 201);
    }

    public function usersShow(User $user)
    {
        return response()->json(
            $user->load('roles.permissions')
        );
    }

    public function usersUpdate(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
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
            $userData['password'] = Hash::make($validated['password']);
        }

        if (!empty($userData)) {
            $user->update($userData);
        }

        if (isset($validated['role'])) {
            $user->syncRoles($validated['role']);
        }

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => $user->fresh()->load('roles.permissions'),
        ]);
    }

    public function usersDestroy(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    public function rolesIndex()
    {
        return response()->json(
            Role::with('permissions')->latest()->get()
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
            $role->syncPermissions($validated['permissions']);
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

    public function rolesUpdate(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:roles,name,' . $role->id,
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
            'name' => $validated['name'],
        ]);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json([
            'message' => 'Role updated successfully.',
            'data' => $role->fresh()->load('permissions'),
        ]);
    }

    public function rolesDestroy(Role $role)
    {
        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully.',
        ]);
    }

    public function permissionsIndex()
    {
        return response()->json(
            Permission::latest()->get()
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

    public function permissionsShow(Permission $permission)
    {
        return response()->json($permission);
    }

    public function permissionsSync(Request $request)
    {
        $validated = $request->validate([
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
    
        $module = trim($validated['module']);
        $actions = array_values(array_unique($validated['actions']));
    
        return DB::transaction(function () use ($module, $actions) {
            $moduleName = strtolower(
                preg_replace('/\s+/', '-', $module)
            );
    
            $existingPermissions = Permission::where(
                'module',
                $module
            )->get();
    
            $existingActions = [];
    
            foreach ($existingPermissions as $permission) {
                $parts = explode('.', $permission->name);
                $action = end($parts);
    
                if (in_array(
                    $action,
                    ['view', 'create', 'update', 'delete']
                )) {
                    $existingActions[] = $action;
                }
            }
    
            foreach ($actions as $action) {
                if (!in_array($action, $existingActions)) {
                    Permission::create([
                        'name' => "{$moduleName}.{$action}",
                        'module' => $module,
                        'guard_name' => 'web',
                    ]);
                }
            }
    
            foreach ($existingPermissions as $permission) {
                $parts = explode('.', $permission->name);
                $action = end($parts);
    
                if (!in_array($action, $actions)) {
                    $permission->delete();
                }
            }
    
            return response()->json([
                'message' => 'Permission updated successfully.',
                'data' => Permission::where(
                    'module',
                    $module
                )->get(),
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
                'unique:permissions,name,' . $permission->id,
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
        ]);

        return response()->json([
            'message' => 'Permission updated successfully.',
            'data' => $permission->fresh(),
        ]);
    }

    public function permissionsDestroy(Permission $permission)
    {
        $permission->delete();

        return response()->json([
            'message' => 'Permission deleted successfully.',
        ]);
    }
}