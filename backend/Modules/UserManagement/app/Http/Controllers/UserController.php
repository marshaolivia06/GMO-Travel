<?php

namespace Modules\UserManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\UserManagement\Repositories\UserRepository;
use Modules\UserManagement\Services\UserService;

class UserController extends Controller
{
    public function __construct(
        private UserRepository $userRepository,
        private UserService $userService
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json(
            $this->userRepository->getAll()
        );
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(
            $this->userRepository->findById($id)
        );
    }

    public function store(Request $request): JsonResponse
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

        return response()->json([
            'message' => 'User created successfully.',
            'data' => $this->userService->createUser($validated),
        ], 201);
    }

    public function update(
        Request $request,
        int $id
    ): JsonResponse {
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
                'unique:users,email,' . $id,
            ],
            'password' => [
                'sometimes',
                'nullable',
                'string',
                'min:8',
            ],
            'role' => [
                'sometimes',
                'required',
                'string',
                'exists:roles,name',
            ],
        ]);

        return response()->json(
            $this->userService->updateUser($id, $validated)
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $user = $this->userRepository->findById($id);

        $this->userRepository->delete($user);

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }
}
