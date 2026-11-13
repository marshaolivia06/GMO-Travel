<?php

use Illuminate\Support\Facades\Route;
use Modules\TravelExpenseReport\Http\Controllers\TravelExpenseReportController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('travel-expense-reports', TravelExpenseReportController::class)->names('travelexpensereport');
});
