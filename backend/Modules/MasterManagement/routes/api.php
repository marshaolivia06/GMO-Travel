<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\MasterManagement\Http\Controllers\CategoryController;
use Modules\MasterManagement\Http\Controllers\DepartmentController;
use Modules\MasterManagement\Http\Controllers\SectionController;

Route::middleware(['auth:sanctum'])
    ->prefix('v1')
    ->group(function () {

        // CATEGORIES
        Route::apiResource(
            'master-management/categories',
            CategoryController::class
        );

        // DEPARTMENTS
        Route::get(
            'master-management/departments/options',
            [DepartmentController::class, 'options']
        );

        Route::apiResource(
            'master-management/departments',
            DepartmentController::class
        );

        // SECTIONS
        Route::get(
            'master-management/sections/options',
            [SectionController::class, 'options']
        );

        Route::apiResource(
            'master-management/sections',
            SectionController::class
        );
    });
