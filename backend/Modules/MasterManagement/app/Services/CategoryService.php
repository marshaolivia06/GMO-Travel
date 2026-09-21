<?php

declare(strict_types=1);

namespace Modules\MasterManagement\Services;

use Modules\MasterManagement\Repositories\CategoryRepositoryInterface;

class CategoryService
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository
    ) {
    }

    public function paginate(int $perPage = 15, ?string $search = null)
    {
        return $this->categoryRepository->paginate($perPage, $search);
    }

    public function findById(int $id)
    {
        return $this->categoryRepository->findById($id);
    }

    public function create(array $data)
    {
        return $this->categoryRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->categoryRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->categoryRepository->delete($id);
    }

    public function isUsedByDepartment(int $id): bool
    {
        return $this->categoryRepository->isUsedByDepartment($id);
    }
}
