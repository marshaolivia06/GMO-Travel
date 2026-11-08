<?php

use Illuminate\Support\Facades\Route;
use Modules\AuthorityMatrix\Http\Controllers\AuthorityMatrixController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('authoritymatrices', AuthorityMatrixController::class)
        ->names('authoritymatrix')
        ->middlewareFor(['index', 'show'], 'permission:authority-matrix.view')
        ->middlewareFor('store', 'permission:authority-matrix.create')
        ->middlewareFor('update', 'permission:authority-matrix.update')
        ->middlewareFor('destroy', 'permission:authority-matrix.delete');
});
