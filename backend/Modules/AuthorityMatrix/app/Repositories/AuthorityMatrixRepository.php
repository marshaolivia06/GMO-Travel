<?php

namespace Modules\AuthorityMatrix\Repositories;

use Modules\AuthorityMatrix\Models\AuthorityMatrix;

class AuthorityMatrixRepository
{
    private const RELATIONS = ['steps.role', 'steps.section', 'steps.department'];

    public function all()
    {
        return AuthorityMatrix::with(self::RELATIONS)
            ->where('status', 1)
            ->get();
    }

    public function find($id)
    {
        return AuthorityMatrix::with(self::RELATIONS)
            ->where('status', 1)
            ->findOrFail($id);
    }

    public function create(array $data)
    {
        return AuthorityMatrix::create($data);
    }

    public function update(AuthorityMatrix $matrix, array $data)
    {
        $matrix->update($data);

        return $matrix;
    }

    // soft delete manual: status 0 = dianggap terhapus di UI
    public function delete(AuthorityMatrix $matrix)
    {
        return $matrix->update(['status' => 0]);
    }
}