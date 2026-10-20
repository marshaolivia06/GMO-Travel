<?php

use Illuminate\Support\Facades\Route;
use Modules\TravelOrder\Http\Controllers\{TravelAdvanceMasterController, TravelOrderController, TravelOrderGroupController};

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::prefix('travel-orders')->group(function () {
        Route::get('/', [TravelOrderController::class, 'index'])->middleware('permission:travel-order.view');
        Route::get('/department-lock', [TravelOrderController::class, 'departmentLock'])->middleware('permission:travel-order.create');
        Route::post('/', [TravelOrderController::class, 'store'])->middleware('permission:travel-order.create');

        Route::put('/{id}/gmo/schedule', [TravelOrderController::class, 'updateGmoSchedule'])->middleware('permission:travel-order.view');
        Route::post('/{id}/gmo/booking-items', [TravelOrderController::class, 'updateGmoBookingItems'])->middleware('permission:travel-order.view');
        Route::post('/{id}/gmo/cancellations/{itemType}', [TravelOrderController::class, 'cancelGmoBookingItem'])->middleware('permission:travel-order.view');
        Route::post('/{id}/gmo/resend-email', [TravelOrderController::class, 'resendGmoBookingEmail'])->middleware('permission:travel-order.view');

        Route::get('/{id}', [TravelOrderController::class, 'show'])->middleware('permission:travel-order.view');
        Route::get('/{id}/pdf', [TravelOrderController::class, 'pdf'])->middleware('permission:travel-order.view');
        Route::get('/{id}/history', [TravelOrderController::class, 'history'])->middleware('permission:travel-order.view');
        Route::put('/{id}', [TravelOrderController::class, 'update'])->middleware('permission:travel-order.create');

        Route::post('/{id}/approve', [TravelOrderController::class, 'approve'])->middleware('permission:travel-order.approve');
        Route::post('/{id}/reject', [TravelOrderController::class, 'reject'])->middleware('permission:travel-order.approve');
        Route::post('/{id}/cancel', [TravelOrderController::class, 'cancel']);
        Route::post('/{id}/revision', [TravelOrderController::class, 'revision']);
        Route::post('/{id}/complete-gmo-processing', [TravelOrderController::class, 'completeGmoProcessing'])->middleware('permission:travel-order.view');
        Route::get('group-trip/users', [TravelOrderGroupController::class, 'users'])->middleware('permission:travel-order.update');
    });

    // GROUP TRAVEL ORDER
    Route::prefix('travel-order-groups')->group(function () {
    Route::get('/', [TravelOrderGroupController::class, 'index'])->middleware('permission:travel-order.update');
    Route::post('/', [TravelOrderGroupController::class, 'store'])->middleware('permission:travel-order.update');

    Route::get('/{id}', [TravelOrderGroupController::class, 'show'])->middleware('permission:travel-order.update');
    Route::put('/{id}', [TravelOrderGroupController::class, 'update'])->middleware('permission:travel-order.update');
    Route::delete('/{id}', [TravelOrderGroupController::class, 'destroy'])->middleware('permission:travel-order.delete');

    Route::post('/{id}/open', [TravelOrderGroupController::class, 'open'])->middleware('permission:travel-order.update');
    Route::post('/{id}/close', [TravelOrderGroupController::class, 'close'])->middleware('permission:travel-order.update');

    Route::post('/{id}/members', [TravelOrderGroupController::class, 'addMembers'])->middleware('permission:travel-order.update');
    Route::delete('/{id}/members/{userId}', [TravelOrderGroupController::class, 'removeMember'])->middleware('permission:travel-order.update');
});

    // TRAVEL ADVANCE MASTER
    Route::get('travel-advance-masters/limit', [TravelAdvanceMasterController::class, 'limit'])->middleware('permission:travel-order.create');
    Route::get('travel-advance-masters/limit-by-grade', [TravelAdvanceMasterController::class, 'limitByGrade'])->middleware('permission:travel-order.create');
    Route::get('travel-advance-masters/regions', [TravelAdvanceMasterController::class, 'regions'])->middleware('permission:travel-order.create');
    Route::get('travel-advance-masters/countries', [TravelAdvanceMasterController::class, 'countries'])->middleware('permission:travel-order.create');
    Route::apiResource('travel-advance-masters', TravelAdvanceMasterController::class);
});
