<?php

declare(strict_types=1);

namespace Modules\MasterManagement\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Modules\MasterManagement\Http\Requests\StoreCategoryRequest;
use Modules\MasterManagement\Http\Requests\UpdateCategoryRequest;
use Modules\MasterManagement\Http\Resources\CategoryResource;
use Modules\MasterManagement\Services\CategoryService;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $categoryService
    ) {
    }

    public function index(HttpRequest $request): JsonResponse
    {
        $categories = $this->categoryService->paginate(
            $request->integer('per_page', 15),
            $request->query('search')
        );

        return ApiResponse::success(
            CategoryResource::collection($categories)->response()->getData(true)
        );
    }

    public function show(int $id): JsonResponse
    {
        return ApiResponse::success(
            new CategoryResource($this->categoryService->findById($id))
        );
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create(
            $request->validated()
        );

        return ApiResponse::success(new CategoryResource($category), 'Category created successfully.', 201);
    }

    public function update(
        UpdateCategoryRequest $request,
        int $id
    ): JsonResponse {
        $category = $this->categoryService->update(
            $id,
            $request->validated()
        );

        return ApiResponse::success(new CategoryResource($category), 'Category updated successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->categoryService->findById($id);

        if ($this->categoryService->isUsedByDepartment($id)) {
            abort(
                422,
                'Category cannot be deleted because it is already being used by a department.'
            );
        }

        $this->categoryService->delete($id);

        return ApiResponse::success(null, 'Category deleted successfully.');
    }
}
