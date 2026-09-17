<?php

namespace Modules\MasterManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\MasterManagement\Repositories\SectionRepository;

class SectionController extends Controller
{
    public function __construct(
        private SectionRepository $sectionRepository
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json(
            $this->sectionRepository->getAll()
        );
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(
            $this->sectionRepository->findById($id)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:sections,name',
            ],
            'section_head_id' => [
                'required',
                'exists:users,id',
            ],
            'department_id' => [
                'required',
                'exists:departments,id',
            ],
        ]);

        return response()->json([
            'message' => 'Section created successfully.',
            'data' => $this->sectionRepository->create(
                $validated
            ),
        ], 201);
    }

    public function update(
        Request $request,
        int $id
    ): JsonResponse {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:sections,name,' . $id,
            ],
            'section_head_id' => [
                'required',
                'exists:users,id',
            ],
            'department_id' => [
                'required',
                'exists:departments,id',
            ],
        ]);

        $section = $this->sectionRepository
            ->findById($id);

        return response()->json([
            'message' => 'Section updated successfully.',
            'data' => $this->sectionRepository->update(
                $section,
                $validated
            ),
        ]);
    }

    public function destroy(int $id): JsonResponse
{
    $section = $this->sectionRepository
        ->findById($id);

    $this->sectionRepository->delete($section);

    return response()->json([
        'message' => 'Section deleted successfully.',
    ]);
}

public function options(): JsonResponse
{
    return response()->json(
        $this->sectionRepository->getOptions()
    );
}

}
