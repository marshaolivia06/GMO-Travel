<?php

use Illuminate\Support\Facades\Route;
use Modules\MasterManagement\Http\Controllers\CategoryController;
use Modules\MasterManagement\Http\Controllers\DepartmentController;
use Modules\MasterManagement\Http\Controllers\SectionController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    // CATEGORIES
    Route::get('master-management/categories', [
        CategoryController::class,
        'index',
    ]);

    Route::post('master-management/categories', [
        CategoryController::class,
        'store',
    ]);

    Route::get('master-management/categories/{id}', [
        CategoryController::class,
        'show',
    ]);

    Route::put('master-management/categories/{id}', [
        CategoryController::class,
        'update',
    ]);

    Route::delete('master-management/categories/{id}', [
        CategoryController::class,
        'destroy',
    ]);

    // DEPARTMENTS
    Route::get('master-management/departments/options', [
        DepartmentController::class,
        'options',
    ]);

    Route::get('master-management/departments', [
        DepartmentController::class,
        'index',
    ]);

    Route::post('master-management/departments', [
        DepartmentController::class,
        'store',
    ]);

    Route::get('master-management/departments/{id}', [
        DepartmentController::class,
        'show',
    ]);

    Route::put('master-management/departments/{id}', [
        DepartmentController::class,
        'update',
    ]);

    Route::delete('master-management/departments/{id}', [
        DepartmentController::class,
        'destroy',
    ]);

   // SECTIONS
Route::get('master-management/sections/options', [
    SectionController::class,
    'options',
]);

Route::get('master-management/sections', [
    SectionController::class,
    'index',
]);

Route::post('master-management/sections', [
    SectionController::class,
    'store',
]);

Route::get('master-management/sections/{id}', [
    SectionController::class,
    'show',
]);

Route::put('master-management/sections/{id}', [
    SectionController::class,
    'update',
]);

Route::delete('master-management/sections/{id}', [
    SectionController::class,
    'destroy',
]);


});
