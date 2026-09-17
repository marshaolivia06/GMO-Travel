<?php

namespace Modules\UserManagement\Services;

use Illuminate\Support\Facades\Hash;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Repositories\UserRepository;

class UserService
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    public function createUser(array $data)
    {
        $role = Role::where('name', $data['role'])
            ->where('guard_name', 'web')
            ->firstOrFail();

        $user = $this->userRepository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole($role);

        return $user->fresh()->load('roles.permissions');
    }

    public function updateUser(int $id, array $data)
    {
        $user = $this->userRepository->findById($id);

        if (isset($data['password']) && $data['password'] !== '') {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if (isset($data['role'])) {
            $role = Role::where('name', $data['role'])
                ->where('guard_name', 'web')
                ->firstOrFail();

            unset($data['role']);

            $user = $this->userRepository->update(
                $user,
                $data
            );

            $user->syncRoles([$role]);

            return $user->fresh()->load('roles.permissions');
        }

        return $this->userRepository->update(
            $user,
            $data
        );
    }
}
