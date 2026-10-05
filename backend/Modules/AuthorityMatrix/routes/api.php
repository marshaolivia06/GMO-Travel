<?php

use Illuminate\Support\Facades\Route;
use Modules\AuthorityMatrix\Http\Controllers\AuthorityMatrixController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('authoritymatrices', AuthorityMatrixController::class)->names('authoritymatrix');
});
