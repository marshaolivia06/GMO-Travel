<?php

namespace Modules\MasterManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\MasterManagement\Repositories\DepartmentRepository;

class DepartmentController extends Controller
{
    public function __construct(
        private DepartmentRepository $departmentRepository
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json(
            $this->departmentRepository->getAll()
        );
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(
            $this->departmentRepository->findById($id)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:departments,name',
            ],
            'category_id' => [
                'required',
                'exists:categories,id',
            ],
            'dept_head_id' => [
                'required',
                'exists:users,id',
            ],
            'dept_admin_id' => [
                'required',
                'exists:users,id',
            ],
            'division_head_id' => [
                'required',
                'exists:master_division,id',
            ],
        ]);

        $department = $this->departmentRepository->create([
            'name' => trim($validated['name']),
            'category_id' => $validated['category_id'],
            'dept_head_id' => $validated['dept_head_id'],
            'dept_admin_id' => $validated['dept_admin_id'],
            'division_head_id' => $validated['division_head_id'],
        ]);

        return response()->json([
            'message' => 'Department created successfully.',
            'data' => $department,
        ], 201);
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
                'unique:departments,name,' . $id,
            ],
            'category_id' => [
                'required',
                'exists:categories,id',
            ],
            'dept_head_id' => [
                'required',
                'exists:users,id',
            ],
            'dept_admin_id' => [
                'required',
                'exists:users,id',
            ],
            'division_head_id' => [
                'required',
                'exists:master_division,id',
            ],
        ]);

        $department = $this->departmentRepository->findById($id);

        $department = $this->departmentRepository->update(
            $department,
            [
                'name' => trim($validated['name']),
                'category_id' => $validated['category_id'],
                'dept_head_id' => $validated['dept_head_id'],
                'dept_admin_id' => $validated['dept_admin_id'],
                'division_head_id' => $validated['division_head_id'],
            ]
        );

        return response()->json([
            'message' => 'Department updated successfully.',
            'data' => $department,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $department = $this->departmentRepository->findById($id);

        $this->departmentRepository->delete($department);

        return response()->json([
            'message' => 'Department deleted successfully.',
        ]);
    }

    public function options(): JsonResponse
    {
        return response()->json(
            $this->departmentRepository->getOptions()
        );
    }
}
