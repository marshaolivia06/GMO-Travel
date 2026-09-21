<?php

declare(strict_types=1);

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
        $this->categoryRepository->findById($id);

        if ($this->categoryRepository->isUsedByDepartment($id)) {
            abort(
                422,
                'Category cannot be deleted because it is already being used by a department.'
            );
        }

        return $this->categoryRepository->delete($id);
    }
}
