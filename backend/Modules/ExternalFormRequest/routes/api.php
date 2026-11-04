<?php

use Illuminate\Support\Facades\Route;
use Modules\ExternalFormRequest\Http\Controllers\ExternalFormRequestController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('externalformrequests', [ExternalFormRequestController::class, 'index'])
        ->middleware('permission:external-form-request.view');

    Route::get('externalformrequests/{id}/history', [ExternalFormRequestController::class, 'history'])
        ->middleware('permission:external-form-request.view');

    Route::post('externalformrequests', [ExternalFormRequestController::class, 'store'])
        ->middleware('permission:external-form-request.create');

    Route::put('externalformrequests/{id}', [ExternalFormRequestController::class, 'update'])
        ->middleware('permission:external-form-request.create');
});
