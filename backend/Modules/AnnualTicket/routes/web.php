<?php

use Illuminate\Support\Facades\Route;
use Modules\AnnualTicket\Http\Controllers\AnnualTicketController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('annualtickets', AnnualTicketController::class)->names('annualticket');
});
