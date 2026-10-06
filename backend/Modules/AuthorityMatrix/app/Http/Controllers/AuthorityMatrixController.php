<?php

namespace Modules\AuthorityMatrix\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\AuthorityMatrix\Http\Requests\StoreAuthorityMatrixRequest;
use Modules\AuthorityMatrix\Http\Requests\UpdateAuthorityMatrixRequest;
use Modules\AuthorityMatrix\Services\AuthorityMatrixService;

class AuthorityMatrixController extends Controller
{
    public function __construct(protected AuthorityMatrixService $service) {}

    public function index()
    {
        return response()->json($this->service->getAll());
    }

    public function store(StoreAuthorityMatrixRequest $request)
    {
        $matrix = $this->service->create($request->validated());

        return response()->json([
            'message' => 'Authority Matrix berhasil dibuat.',
            'data' => $matrix,
        ], 201);
    }

    public function show($id)
    {
        return response()->json([
            'data' => $this->service->getById($id),
        ]);
    }

    public function update(UpdateAuthorityMatrixRequest $request, $id)
    {
        $matrix = $this->service->update($id, $request->validated());

        return response()->json([
            'message' => 'Authority Matrix berhasil diperbarui.',
            'data' => $matrix,
        ]);
    }

    public function destroy($id)
    {
        $this->service->delete($id);

        return response()->json([
            'message' => 'Authority Matrix berhasil dihapus.',
        ]);
    }
}