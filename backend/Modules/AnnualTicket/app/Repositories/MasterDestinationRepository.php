<?php

namespace Modules\AnnualTicket\Repositories;

use Modules\AnnualTicket\Models\MasterDestination;

class MasterDestinationRepository
{
    public function all()
    {
        return MasterDestination::orderBy('city_name')->get();
    }

    public function find(int $id): MasterDestination
    {
        return MasterDestination::findOrFail($id);
    }

    public function create(array $data): MasterDestination
    {
        return MasterDestination::create($data);
    }

    public function update(int $id, array $data): MasterDestination
    {
        $destination = MasterDestination::findOrFail($id);

        $destination->update($data);

        return $destination->refresh();
    }

    public function delete(int $id): void
    {
        MasterDestination::findOrFail($id)->delete();
    }
}