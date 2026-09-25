<?php

namespace Modules\TravelOrder\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\TravelOrder\Models\TravelOrder;

class TravelOrderRepository
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return TravelOrder::with(['user:id,name', 'department:id,name'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                        ->orWhere('travel_from', 'like', "%{$search}%")
                        ->orWhere('travel_to', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['travel_region'] ?? null, fn ($query, $region) => $query->where('travel_region', $region))
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id): TravelOrder
    {
        return TravelOrder::with(['user:id,name', 'department:id,name', 'creator:id,name'])
            ->findOrFail($id);
    }

    public function create(array $data): TravelOrder
    {
        return TravelOrder::create($data);
    }

    public function lastOrderNumberWithPrefix(string $prefix): ?string
    {
        return TravelOrder::withTrashed()
            ->where('order_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('order_number')
            ->value('order_number');
    }
}