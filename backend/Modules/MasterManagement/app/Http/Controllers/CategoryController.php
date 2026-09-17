<?php

namespace Modules\MasterManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\MasterManagement\Repositories\CategoryRepository;
use Modules\MasterManagement\Services\CategoryService;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private CategoryService $categoryService
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json(
            $this->categoryRepository->getAll()
        );
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(
            $this->categoryRepository->findById($id)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:categories,name',
            ],
        ]);

        $category = $this->categoryRepository->create([
            'name' => trim($validated['name']),
        ]);

        return response()->json([
            'message' => 'Category created successfully.',
            'data' => $category,
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
                'unique:categories,name,' . $id,
            ],
        ]);

        $category = $this->categoryRepository->findById($id);

        $category = $this->categoryRepository->update(
            $category,
            [
                'name' => trim($validated['name']),
            ]
        );

        return response()->json([
            'message' => 'Category updated successfully.',
            'data' => $category,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->categoryService->deleteCategory($id);

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }
}
