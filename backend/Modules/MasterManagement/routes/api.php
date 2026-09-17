<?php

use Illuminate\Support\Facades\Route;
use Modules\MasterManagement\Http\Controllers\CategoryController;
use Modules\MasterManagement\Http\Controllers\DepartmentController;

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

});
