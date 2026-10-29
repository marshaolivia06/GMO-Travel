<?php

namespace Modules\AnnualTicket\Services;

use Modules\AnnualTicket\Repositories\MasterDependentRepository;

class MasterDependentService
{
    private const MAX_ELIGIBLE_AGE = 23;
    public function __construct(private MasterDependentRepository $repository) {}

    public function listGrouped()
    {
    return $this->repository->groupedByEmployee();
    }

    public function travelersFor($user): array
{
    $user->load('eligibleDestination');

    $dependents = $this->repository->forEmployee($user->id)->map(function ($dependent) {
        $age = $dependent->date_of_birth->age;

        return ['id' => $dependent->id, 'name' => $dependent->name, 'relationship' => $dependent->relationship, 'dob' => $dependent->date_of_birth->format('Y-m-d'), 'age' => $age, 'eligible' => $dependent->relationship === 'Spouse' || $age < self::MAX_ELIGIBLE_AGE];
    });

    return ['employee' => ['id' => $user->id, 'name' => $user->name], 'eligible_destination' => $user->eligibleDestination?->city_name, 'dependents' => $dependents->values()];
}

    public function createForEmployee(array $data): void
    {
    $this->repository->createForEmployee($data['employee_id'], $data['dependents']);
    }

    public function availableEmployees()
    {
    return $this->repository->availableEmployees();
    }

    public function syncForEmployee(int $employeeId, array $dependents): void
{
    $this->repository->syncForEmployee($employeeId, $dependents);
}

public function deleteForEmployee(int $employeeId): void
{
    $this->repository->deleteForEmployee($employeeId);
}
}