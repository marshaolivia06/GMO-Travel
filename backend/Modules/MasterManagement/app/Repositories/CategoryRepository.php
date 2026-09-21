<?php

declare(strict_types=1);

namespace Modules\MasterManagement\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CategoryRepository
{
    public function getAll(): Collection
    {
        return DB::table('categories')
            ->where('status', 1)
            ->orderByDesc('created_at')
            ->get();
    }

    public function findById(int $id)
    {
        $category = DB::table('categories')
            ->where('status', 1)
            ->where('id', $id)
            ->first();

        if (!$category) {
            abort(404);
        }

        return $category;
    }

    public function create(array $data)
    {
        $now = now();

        $id = DB::table('categories')->insertGetId([
            'name' => $data['name'],
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->findById($id);
    }

    public function update(int $id, array $data)
    {
        DB::table('categories')
            ->where('id', $id)
            ->where('status', 1)
            ->update([
                'name' => $data['name'],
                'updated_at' => now(),
            ]);

        return $this->findById($id);
    }

    public function isUsedByDepartment(int $categoryId): bool
    {
        return DB::table('departments')
            ->where('category_id', $categoryId)
            ->exists();
    }

    public function delete(int $id): bool
    {
        return DB::table('categories')
            ->where('id', $id)
            ->where('status', 1)
            ->update([
                'status' => 0,
                'updated_at' => now(),
            ]) > 0;
    }
}
