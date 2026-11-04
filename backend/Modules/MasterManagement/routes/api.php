<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\MasterManagement\Http\Controllers\CategoryController;
use Modules\MasterManagement\Http\Controllers\DepartmentController;
use Modules\MasterManagement\Http\Controllers\SectionController;

Route::middleware(['auth:sanctum'])
    ->prefix('v1')
    ->group(function () {

        // CATEGORIES - dikelola Admin saja
        Route::apiResource(
            'master-management/categories',
            CategoryController::class
        )->middlewareFor(['index', 'show'], 'permission:master-management.view')
         ->middlewareFor('store', 'permission:master-management.create')
         ->middlewareFor('update', 'permission:master-management.update')
         ->middlewareFor('destroy', 'permission:master-management.delete');

        // DEPARTMENTS
        // options dipakai form Travel Order - longgar untuk pembuat travel order
        Route::get(
            'master-management/departments/options',
            [DepartmentController::class, 'options']
        )->middleware('permission:travel-order.create');

        Route::apiResource(
            'master-management/departments',
            DepartmentController::class
        )->middlewareFor(['index', 'show'], 'permission:master-management.view')
         ->middlewareFor('store', 'permission:master-management.create')
         ->middlewareFor('update', 'permission:master-management.update')
         ->middlewareFor('destroy', 'permission:master-management.delete');

        // SECTIONS
        // my-section dipakai form Travel Order - longgar untuk pembuat travel order
        Route::get(
            'master-management/sections/my-section',
            [SectionController::class, 'mySection']
        )->middleware('permission:travel-order.create');

        // options dipakai form Authority Matrix - admin saja
        Route::get(
            'master-management/sections/options',
            [SectionController::class, 'options']
        )->middleware('permission:master-management.view');

        Route::apiResource(
            'master-management/sections',
            SectionController::class
        )->middlewareFor(['index', 'show'], 'permission:master-management.view')
         ->middlewareFor('store', 'permission:master-management.create')
         ->middlewareFor('update', 'permission:master-management.update')
         ->middlewareFor('destroy', 'permission:master-management.delete');
    });
