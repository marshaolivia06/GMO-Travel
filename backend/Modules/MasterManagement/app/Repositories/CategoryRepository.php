<?php

declare(strict_types=1);

namespace Modules\MasterManagement\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\MasterManagement\Models\Category;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function query(): Builder
    {
        return Category::query();
    }

    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return $this->query()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): Category
    {
        return $this->query()->findOrFail($id);
    }

    public function create(array $data): Category
    {
        return Category::create([
            'name' => $data['name'],
            'status' => true,
        ]);
    }

    public function update(int $id, array $data): Category
    {
        $category = $this->findById($id);

        $category->update([
            'name' => $data['name'],
        ]);

        return $category;
    }

    public function delete(int $id): bool
    {
        return $this->findById($id)->delete();
    }

    public function isUsedByDepartment(int $id): bool
    {
        return $this->findById($id)->departments()->exists();
    }
}
