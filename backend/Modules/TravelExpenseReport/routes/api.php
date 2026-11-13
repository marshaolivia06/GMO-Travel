<?php

use Illuminate\Support\Facades\Route;
use Modules\TravelExpenseReport\Http\Controllers\TravelExpenseReportController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::middleware('permission:travel-expense-report.view')->group(function () {
        Route::get('travel-expense-reports', [TravelExpenseReportController::class, 'index']);
        Route::get('travel-expense-reports/{id}', [TravelExpenseReportController::class, 'show']);
        Route::get('travel-expense-reports/{id}/history', [TravelExpenseReportController::class, 'history']);
        Route::get('travel-expense-reports/{id}/pdf', [TravelExpenseReportController::class, 'pdf']);
    });

    Route::middleware('permission:travel-expense-report.create')->group(function () {
        Route::post('travel-expense-reports', [TravelExpenseReportController::class, 'store']);
        Route::post('travel-expense-reports/{id}/submit', [TravelExpenseReportController::class, 'submit']);
    });

    Route::middleware('permission:travel-expense-report.update')->group(function () {
        Route::post('travel-expense-reports/{id}/update', [TravelExpenseReportController::class, 'update']);
    });

    Route::middleware('permission:travel-expense-report.delete')->group(function () {
        Route::delete('travel-expense-reports/{id}', [TravelExpenseReportController::class, 'destroy']);
    });
});
