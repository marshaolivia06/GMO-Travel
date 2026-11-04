<?php

use Illuminate\Support\Facades\Route;
use Modules\AnnualTicket\Http\Controllers\AnnualTicketController;
use Modules\AnnualTicket\Http\Controllers\MasterDependentController;
use Modules\AnnualTicket\Http\Controllers\MasterDestinationController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
// Aksi pemohon (requester): lihat daftar, kuota, dokumen & buat/edit/batalkan pengajuan sendiri
Route::middleware('permission:annual-ticket.view')->group(function () {
    Route::get('annualtickets/quota', [AnnualTicketController::class, 'quota']);
    Route::get('annualtickets/{id}/history', [AnnualTicketController::class, 'history']);
    Route::get('annualtickets/{id}/pdf', [AnnualTicketController::class, 'pdf']);
    Route::get('master-dependents/my-dependents', [MasterDependentController::class, 'myDependents']);
});

Route::middleware('permission:annual-ticket.create')->group(function () {
    Route::post('annualtickets', [AnnualTicketController::class, 'store']);
    Route::post('annualtickets/{id}/resubmit', [AnnualTicketController::class, 'resubmit']);
    Route::post('annualtickets/{id}/cancel-request', [AnnualTicketController::class, 'cancelRequest']);
});

// Aksi alur approval & pemrosesan (HR Admin, HR Supervisor, Admin-GMO, approver manajemen)
Route::middleware('permission:annual-ticket.approve')->group(function () {
    Route::post('annualtickets/{id}/check', [AnnualTicketController::class, 'check']);
    Route::post('annualtickets/{id}/approve', [AnnualTicketController::class, 'approve']);
    Route::post('annualtickets/{id}/revision', [AnnualTicketController::class, 'revision']);
    Route::post('annualtickets/{id}/process', [AnnualTicketController::class, 'process']);
    Route::post('annualtickets/{id}/schedule', [AnnualTicketController::class, 'updateSchedule']);
    Route::post('annualtickets/{id}/booking-documents', [AnnualTicketController::class, 'updateBookingDocuments']);
    Route::post('annualtickets/{id}/legs/{legId}/cancel', [AnnualTicketController::class, 'cancelLeg']);
    Route::post('annualtickets/{id}/legs/{legId}/refund', [AnnualTicketController::class, 'updateLegRefund']);
    Route::post('annualtickets/{id}/resend-email', [AnnualTicketController::class, 'resendEmail']);
    Route::post('annualtickets/{id}/cancel-finalize', [AnnualTicketController::class, 'cancelFinalize']);
    Route::post('annualtickets/{id}/cancel-reject', [AnnualTicketController::class, 'cancelReject']);
});

// Daftar tiket - dilihat oleh semua yang terlibat (pemohon, approver, pemroses)
Route::get('annualtickets', [AnnualTicketController::class, 'index'])
    ->middleware('permission:annual-ticket.view');

// Master dependent & destination - dikelola Admin saja
// (my-dependents untuk pemohon sudah digate annual-ticket.view di group atas)
Route::middleware('permission:master-dependent.view')->group(function () {
    Route::get('master-dependents/employee-options', [MasterDependentController::class, 'employeeOptions']);
    Route::get('master-dependents', [MasterDependentController::class, 'index']);
});

Route::middleware('permission:master-dependent.create')->group(function () {
    Route::post('master-dependents', [MasterDependentController::class, 'store']);
});

Route::middleware('permission:master-dependent.update')->group(function () {
    Route::put('master-dependents/{employeeId}', [MasterDependentController::class, 'update']);
});

Route::middleware('permission:master-dependent.delete')->group(function () {
    Route::delete('master-dependents/{employeeId}', [MasterDependentController::class, 'destroy']);
});

Route::middleware('permission:master-destination.view')->group(function () {
    Route::get('master-destinations', [MasterDestinationController::class, 'index']);
});

Route::middleware('permission:master-destination.create')->group(function () {
    Route::post('master-destinations', [MasterDestinationController::class, 'store']);
});

Route::middleware('permission:master-destination.update')->group(function () {
    Route::put('master-destinations/{id}', [MasterDestinationController::class, 'update']);
});

Route::middleware('permission:master-destination.delete')->group(function () {
    Route::delete('master-destinations/{id}', [MasterDestinationController::class, 'destroy']);
});
});