<?php

namespace Modules\UserManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{
    public function officeLocations(): JsonResponse
    {
        return response()->json([
            'data' => config('company.office_locations'),
        ]);
    }
}
