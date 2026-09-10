<?php

namespace Modules\MasterManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MasterManagement\Models\Category;

class MasterManagementController extends Controller
{
    public function categoriesIndex()
    {
        return response()->json(
            Category::latest()->get()
        );
    }

    public function categoriesStore(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:categories,name',
            ],
        ]);

        $category = Category::create([
            'name' => trim($validated['name']),
        ]);

        return response()->json([
            'message' => 'Category created successfully.',
            'data' => $category,
        ], 201);
    }

    public function categoriesShow(Category $category)
    {
        return response()->json($category);
    }

    public function categoriesUpdate(
        Request $request,
        Category $category
    ) {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:categories,name,' . $category->id,
            ],
        ]);

        $category->update([
            'name' => trim($validated['name']),
        ]);

        return response()->json([
            'message' => 'Category updated successfully.',
            'data' => $category->fresh(),
        ]);
    }

    public function categoriesDestroy(Category $category)
    {
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }
}
