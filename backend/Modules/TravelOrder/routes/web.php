<?php

use Illuminate\Support\Facades\Route;
use Modules\TravelOrder\Http\Controllers\TravelOrderController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('travelorders', TravelOrderController::class)->names('travelorder');
});
