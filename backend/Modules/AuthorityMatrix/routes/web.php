<?php

use Illuminate\Support\Facades\Route;
use Modules\AuthorityMatrix\Http\Controllers\AuthorityMatrixController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('authoritymatrices', AuthorityMatrixController::class)->names('authoritymatrix');
});
