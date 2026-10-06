<?php

namespace Modules\AuthorityMatrix\Services;

use Illuminate\Support\Facades\DB;
use Modules\AuthorityMatrix\Repositories\AuthorityMatrixRepository;

class AuthorityMatrixService
{
    public function __construct(protected AuthorityMatrixRepository $repository) {}

    public function getAll()
    {
        return $this->repository->all();
    }

    public function getById($id)
    {
        return $this->repository->find($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $matrix = $this->repository->create([
                'document_type' => $data['document_type'],
                'status' => $data['status'] ?? true,
            ]);

            foreach ($data['steps'] as $step) {
                $matrix->steps()->create($step);
            }

            return $matrix->load('steps.role', 'steps.section', 'steps.department');
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $matrix = $this->repository->find($id);

            $this->repository->update($matrix, [
                'document_type' => $data['document_type'],
                'status' => $data['status'] ?? true,
            ]);

            $matrix->steps()->delete();

            foreach ($data['steps'] as $step) {
                $matrix->steps()->create($step);
            }

            return $matrix->load('steps.role', 'steps.section', 'steps.department');
        });
    }

    public function delete($id)
    {
        $matrix = $this->repository->find($id);

        return $this->repository->delete($matrix);
    }
}