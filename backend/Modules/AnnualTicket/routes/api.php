<?php

use Illuminate\Support\Facades\Route;
use Modules\AnnualTicket\Http\Controllers\AnnualTicketController;
use Modules\AnnualTicket\Http\Controllers\MasterDependentController;
use Modules\AnnualTicket\Http\Controllers\MasterDestinationController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
Route::get('annualtickets/{id}/history', [AnnualTicketController::class, 'history']);
Route::get('annualtickets/{id}/pdf', [AnnualTicketController::class, 'pdf']);
Route::apiResource('annualtickets', AnnualTicketController::class)->only(['index', 'store'])->names('annualticket');
Route::get('master-dependents/employee-options', [MasterDependentController::class, 'employeeOptions']);
Route::get('master-dependents/my-dependents', [MasterDependentController::class, 'myDependents']);
Route::get('master-dependents', [MasterDependentController::class, 'index']);
Route::post('master-dependents', [MasterDependentController::class, 'store']);
Route::put('master-dependents/{employeeId}', [MasterDependentController::class, 'update']);
Route::delete('master-dependents/{employeeId}', [MasterDependentController::class, 'destroy']);

Route::get('master-destinations', [MasterDestinationController::class, 'index']);
Route::post('master-destinations', [MasterDestinationController::class, 'store']);
Route::put('master-destinations/{id}', [MasterDestinationController::class, 'update']);
Route::delete('master-destinations/{id}', [MasterDestinationController::class, 'destroy']);
});