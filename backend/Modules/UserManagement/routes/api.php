<?php

use Illuminate\Support\Facades\Route;
use Modules\UserManagement\Http\Controllers\UserController;
use Modules\UserManagement\Http\Controllers\RoleController;
use Modules\UserManagement\Http\Controllers\PermissionController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    // USERS
    Route::get(
        'users',
        [UserController::class, 'index']
    )->middleware('permission:user-management.view');    

    Route::post(
        'users',
        [UserController::class, 'store']
    )->middleware('permission:user-management.create');    

    Route::get(
        'users/{id}',
        [UserController::class, 'show']
    )->middleware('permission:user-management.view');    

    Route::put(
        'users/{id}',
        [UserController::class, 'update']
    )->middleware('permission:user-management.update');
    
    Route::patch(
        'users/{id}',
        [UserController::class, 'update']
    )->middleware('permission:user-management.update');    

    Route::delete(
        'users/{id}',
        [UserController::class, 'destroy']
    )->middleware('permission:user-management.delete');    

    // ROLES
    Route::get(
        'roles',
        [RoleController::class, 'index']
    )->middleware('permission:user-management.view');

    Route::post(
        'roles',
        [RoleController::class, 'store']
    )->middleware('permission:user-management.create');

    Route::get(
        'roles/{id}',
        [RoleController::class, 'show']
    )->middleware('permission:user-management.view');

    Route::put(
     'roles/{id}',
        [RoleController::class, 'update']
    )->middleware('permission:user-management.update');

    Route::patch(
       'roles/{id}',
     [RoleController::class, 'update']
    )->middleware('permission:user-management.update');

    Route::delete(
        'roles/{id}',
        [RoleController::class, 'destroy']
    )->middleware('permission:user-management.delete');

    // PERMISSIONS
Route::get(
    'permissions',
    [PermissionController::class, 'index']
)->middleware('permission:user-management.view');

Route::post(
    'permissions',
    [PermissionController::class, 'store']
)->middleware('permission:user-management.create');

Route::put(
    'permissions/sync',
    [PermissionController::class, 'sync']
)->middleware('permission:user-management.update');

Route::get(
    'permissions/{id}',
    [PermissionController::class, 'show']
)->middleware('permission:user-management.view');

Route::put(
    'permissions/{id}',
    [PermissionController::class, 'update']
)->middleware('permission:user-management.update');

Route::patch(
    'permissions/{id}',
    [PermissionController::class, 'update']
)->middleware('permission:user-management.update');

Route::delete(
    'permissions/{id}',
    [PermissionController::class, 'destroy']
)->middleware('permission:user-management.delete');

});