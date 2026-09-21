<?php

declare(strict_types=1);

namespace Modules\MasterManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\MasterManagement\Http\Requests\Request;
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
        $category = $this->categoryRepository->create(
            $request->validated()
        );

        return response()->json([
            'message' => 'Category created successfully.',
            'data' => $category,
        ], 201);
    }

    public function update(
        Request $request,
        int $id
    ): JsonResponse {
        $category = $this->categoryRepository->update(
            $id,
            $request->validated()
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
