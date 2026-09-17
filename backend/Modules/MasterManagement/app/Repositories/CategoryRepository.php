<?php

namespace Modules\MasterManagement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\MasterManagement\Models\Category;
use Modules\MasterManagement\Models\Department;

class CategoryRepository
{
    public function getAll(): Collection
    {
        return Category::where('status', 1)
            ->latest()
            ->get();
    }

    public function findById(int $id): Category
    {
        return Category::where('status', 1)
            ->findOrFail($id);
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(
        Category $category,
        array $data
    ): Category {
        $category->update($data);

        return $category->fresh();
    }

    public function isUsedByDepartment(int $categoryId): bool
    {
        return Department::where(
            'category_id',
            $categoryId
        )->exists();
    }

    public function delete(Category $category): bool
    {
        return $category->update([
            'status' => 0,
        ]);
    }
}
