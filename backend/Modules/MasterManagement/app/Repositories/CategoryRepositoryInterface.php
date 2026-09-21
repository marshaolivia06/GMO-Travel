<?php

declare(strict_types=1);

namespace Modules\MasterManagement\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\MasterManagement\Models\Category;

interface CategoryRepositoryInterface
{
    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator;

    public function findById(int $id): Category;

    public function create(array $data): Category;

    public function update(int $id, array $data): Category;

    public function delete(int $id): bool;

    public function isUsedByDepartment(int $id): bool;
}
