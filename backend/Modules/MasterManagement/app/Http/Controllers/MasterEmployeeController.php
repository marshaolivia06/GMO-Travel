<?php

declare(strict_types=1);

namespace Modules\MasterManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\MasterManagement\Models\MasterEmployee;
use Modules\UserManagement\Models\User;

class MasterEmployeeController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = User::query()
            ->with(['employee.eligibleDestination:id,city_name,country', 'roles:id,name'])
            ->where('status', 1)
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'admin'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $data = $rows->map(fn (User $user) => [
            'id' => $user->employee?->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name'),
            'grade' => $user->employee?->grade,
            'eligible_destination_id' => $user->employee?->eligible_destination_id,
            'eligible_destination' => $user->employee?->eligibleDestination?->only(['id', 'city_name', 'country']),
            'date_of_join' => $user->employee?->date_of_join,
            'office_location' => $user->employee?->office_location,
            'bank_name' => $user->employee?->bank_name,
            'bank_account_number' => $user->employee?->bank_account_number,
        ]);

        return response()->json(['data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $employee = MasterEmployee::create($validated);

        return response()->json(['message' => 'Employee created successfully.', 'data' => $employee], 201);
    }

    public function update(Request $request, MasterEmployee $master_employee): JsonResponse
    {
        $validated = $this->validatePayload($request, $master_employee);

        $master_employee->update($validated);

        return response()->json(['message' => 'Employee updated successfully.', 'data' => $master_employee]);
    }

    public function destroy(MasterEmployee $master_employee): JsonResponse
    {
        $master_employee->delete();

        return response()->json(['message' => 'Employee deleted successfully.']);
    }

    private function validatePayload(Request $request, ?MasterEmployee $existing = null): array
    {
        return $request->validate([
            'user_id' => [
                $existing ? 'prohibited' : 'required',
                'integer',
                Rule::exists('users', 'id')->where('status', 1),
                $existing
                    ? null
                    : Rule::unique('master_employees', 'user_id'),
            ],
            'grade' => ['nullable', 'integer', 'min:1', 'max:255'],
            'eligible_destination_id' => ['nullable', 'integer', 'exists:master_destinations,id'],
            'date_of_join' => ['nullable', 'date', 'before_or_equal:today'],
            'office_location' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
        ]);
    }
}
