<?php

use Illuminate\Support\Facades\Route;
use Modules\TravelOrder\Http\Controllers\TravelOrderController;
use Modules\TravelOrder\Http\Controllers\TravelAdvanceMasterController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::prefix('travel-orders')->group(function () {
        Route::get('/', [TravelOrderController::class, 'index'])
            ->middleware('permission:travel-order.view');

        Route::get('/department-lock', [TravelOrderController::class, 'departmentLock'])
            ->middleware('permission:travel-order.create');

        Route::get('/{id}', [TravelOrderController::class, 'show'])
            ->middleware('permission:travel-order.view');

        Route::post('/', [TravelOrderController::class, 'store'])
            ->middleware('permission:travel-order.create');
    });

    Route::get('travel-advance-masters/limit', [TravelAdvanceMasterController::class, 'limit'])
        ->middleware('permission:travel-order.create');
});