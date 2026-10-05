<?php

namespace Modules\AuthorityMatrix\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Modules\AuthorityMatrix\Http\Requests\StoreAuthorityMatrixRequest;
use Modules\AuthorityMatrix\Http\Requests\UpdateAuthorityMatrixRequest;
use Modules\AuthorityMatrix\Models\AuthorityMatrix;

class AuthorityMatrixController extends Controller
{
    public function index()
    {
        return response()->json(
            AuthorityMatrix::with('steps.role', 'steps.section', 'steps.department')->get()
        );
    }

    public function create()
    {
        return view('authoritymatrix::create');
    }

    public function store(StoreAuthorityMatrixRequest $request)
    {
        $data = $request->validated();

        $matrix = DB::transaction(function () use ($data) {
            $matrix = AuthorityMatrix::create([
                'document_type' => $data['document_type'],
                'status' => $data['status'] ?? true,
            ]);

            foreach ($data['steps'] as $step) {
                $matrix->steps()->create($step);
            }

            return $matrix->load(
                'steps.role',
                'steps.section',
                'steps.department'
            );
        });

        return response()->json([
            'message' => 'Authority Matrix berhasil dibuat.',
            'data' => $matrix,
        ], 201);
    }

    public function show($id)
    {
        $matrix = AuthorityMatrix::with(
            'steps.role',
            'steps.section',
            'steps.department'
        )->findOrFail($id);

        return response()->json([
            'data' => $matrix,
        ]);
    }

    public function edit($id)
    {
        return view('authoritymatrix::edit');
    }

    public function update(UpdateAuthorityMatrixRequest $request, $id)
    {
        $data = $request->validated();

        $matrix = DB::transaction(function () use ($data, $id) {
            $matrix = AuthorityMatrix::findOrFail($id);

            $matrix->update([
                'document_type' => $data['document_type'],
                'status' => $data['status'] ?? true,
            ]);

            $matrix->steps()->delete();

            foreach ($data['steps'] as $step) {
                $matrix->steps()->create($step);
            }

            return $matrix->load(
                'steps.role',
                'steps.section',
                'steps.department'
            );
        });

        return response()->json([
            'message' => 'Authority Matrix berhasil diperbarui.',
            'data' => $matrix,
        ]);
    }

    public function destroy($id)
{
    $matrix = AuthorityMatrix::findOrFail($id);
    $matrix->delete();

    return response()->json([
        'message' => 'Authority Matrix berhasil dihapus.',
    ]);
}
}