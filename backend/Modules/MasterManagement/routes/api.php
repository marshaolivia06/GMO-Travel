<?php

use Illuminate\Support\Facades\Route;
use Modules\MasterManagement\Http\Controllers\MasterManagementController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('master-management/categories', [
        MasterManagementController::class,
        'categoriesIndex',
    ]);

    Route::post('master-management/categories', [
        MasterManagementController::class,
        'categoriesStore',
    ]);

    Route::get('master-management/categories/{category}', [
        MasterManagementController::class,
        'categoriesShow',
    ]);

    Route::put('master-management/categories/{category}', [
        MasterManagementController::class,
        'categoriesUpdate',
    ]);

    Route::delete('master-management/categories/{category}', [
        MasterManagementController::class,
        'categoriesDestroy',
    ]);
});
