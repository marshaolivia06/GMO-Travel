<?php

namespace Modules\MasterManagement\Services;

use Modules\MasterManagement\Repositories\CategoryRepository;

class CategoryService
{
    public function __construct(
        private CategoryRepository $categoryRepository
    ) {
    }

    public function deleteCategory(int $id): bool
    {
        $category = $this->categoryRepository->findById($id);

        $isUsed = $this->categoryRepository
            ->isUsedByDepartment($category->id);

        if ($isUsed) {
            abort(
                422,
                'Category cannot be deleted because it is already being used by a department.'
            );
        }

        return $this->categoryRepository->delete($category);
    }
}
