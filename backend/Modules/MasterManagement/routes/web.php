<?php

use Illuminate\Support\Facades\Route;
use Modules\MasterManagement\Http\Controllers\MasterManagementController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('mastermanagements', MasterManagementController::class)->names('mastermanagement');
});
