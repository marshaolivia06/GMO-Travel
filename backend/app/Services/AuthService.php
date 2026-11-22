<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\UserManagement\Models\User;

class AuthService
{
    public function login(array $credentials): array
    {
        if (! Auth::guard('web')->once([...$credentials, 'status' => 1])) {
            throw ValidationException::withMessages([
                'email' => ['Invalid email or password.'],
            ]);
        }

        $user = Auth::guard('web')->user();
        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $this->userPayload($user),
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function userPayload(User $user): array
    {
        $user->loadMissing('roles', 'permissions');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ];
    }
}
