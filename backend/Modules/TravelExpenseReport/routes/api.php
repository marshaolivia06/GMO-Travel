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
        Route::post('travel-expense-reports/travel-order', [TravelExpenseReportController::class, 'storeTravelOrder']);
        Route::post('travel-expense-reports/{id}/submit', [TravelExpenseReportController::class, 'submit']);
    });

    // Aksi alur approval (Admin-GMO, approver manajemen, Finance) - izin aksi dicek per tahap di service
    Route::middleware('permission:travel-expense-report.approve')->group(function () {
        Route::post('travel-expense-reports/{id}/check', [TravelExpenseReportController::class, 'check']);
        Route::post('travel-expense-reports/{id}/approve', [TravelExpenseReportController::class, 'approve']);
        Route::post('travel-expense-reports/{id}/revision', [TravelExpenseReportController::class, 'revision']);
    });

    Route::middleware('permission:travel-expense-report.update')->group(function () {
        Route::post('travel-expense-reports/{id}/update', [TravelExpenseReportController::class, 'update']);
        Route::delete('travel-expense-reports/{id}/attachments/{attachmentId}', [TravelExpenseReportController::class, 'deleteAttachment']);
    });

    Route::middleware('permission:travel-expense-report.delete')->group(function () {
        Route::delete('travel-expense-reports/{id}', [TravelExpenseReportController::class, 'destroy']);
    });
});
