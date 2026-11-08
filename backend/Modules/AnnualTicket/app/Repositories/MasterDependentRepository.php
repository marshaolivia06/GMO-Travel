<?php

namespace Modules\AnnualTicket\Repositories;

use Modules\AnnualTicket\Models\MasterDependent;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class MasterDependentRepository
    {
    public function groupedByEmployee()
    {
    return MasterDependent::with('employee:id,name')->orderBy('employee_id')->get()->groupBy('employee_id')->map(fn ($items) => ['id' => $items->first()->employee_id, 'name' => $items->first()->employee->name, 'dependents' => $items->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'relationship' => $d->relationship, 'dob' => $d->date_of_birth->format('Y-m-d')])->values()])->values();
    }

    public function createForEmployee(int $employeeId, array $dependents): void
    {
    DB::transaction(function () use ($employeeId, $dependents) {
        foreach ($dependents as $dependent) {
            MasterDependent::create(['employee_id' => $employeeId, 'name' => $dependent['name'], 'relationship' => $dependent['relationship'], 'date_of_birth' => $dependent['dob']]);
        }
    });
    }

    public function availableEmployees()
    {
    return User::whereNotIn('id', MasterDependent::select('employee_id'))->orderBy('name')->get(['id', 'name']);
    }
    
    public function syncForEmployee(int $employeeId, array $dependents): void
{
    DB::transaction(function () use ($employeeId, $dependents) {
        $keptIds = collect($dependents)->pluck('id')->filter()->all();
        MasterDependent::where('employee_id', $employeeId)->whereNotIn('id', $keptIds)->delete();

        foreach ($dependents as $dependent) {
            $data = ['name' => $dependent['name'], 'relationship' => $dependent['relationship'], 'date_of_birth' => $dependent['dob']];

            if (! empty($dependent['id'])) {
                MasterDependent::where('employee_id', $employeeId)->where('id', $dependent['id'])->update($data);
            } else {
                MasterDependent::create(['employee_id' => $employeeId] + $data);
            }
        }
    });
}
    public function deleteForEmployee(int $employeeId): void
    {
    MasterDependent::where('employee_id', $employeeId)->delete();
    }

    public function forEmployee(int $employeeId)
{
    return MasterDependent::where('employee_id', $employeeId)->orderBy('id')->get();
}
}