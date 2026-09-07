<?php

use Illuminate\Support\Facades\Route;
use Modules\UserManagement\Http\Controllers\UserManagementController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    // USERS
    Route::get(
        'users',
        [UserManagementController::class, 'usersIndex']
    )->middleware('permission:user-management.view');

    Route::post(
        'users',
        [UserManagementController::class, 'usersStore']
    )->middleware('permission:user-management.create');

    Route::get(
        'users/{user}',
        [UserManagementController::class, 'usersShow']
    )->middleware('permission:user-management.view');

    Route::put(
        'users/{user}',
        [UserManagementController::class, 'usersUpdate']
    )->middleware('permission:user-management.update');

    Route::patch(
        'users/{user}',
        [UserManagementController::class, 'usersUpdate']
    )->middleware('permission:user-management.update');

    Route::delete(
        'users/{user}',
        [UserManagementController::class, 'usersDestroy']
    )->middleware('permission:user-management.delete');


    // ROLES
    Route::get(
        'roles',
        [UserManagementController::class, 'rolesIndex']
    )->middleware('permission:user-management.view');

    Route::post(
        'roles',
        [UserManagementController::class, 'rolesStore']
    )->middleware('permission:user-management.create');

    Route::get(
        'roles/{role}',
        [UserManagementController::class, 'rolesShow']
    )->middleware('permission:user-management.view');

    Route::put(
        'roles/{role}',
        [UserManagementController::class, 'rolesUpdate']
    )->middleware('permission:user-management.update');

    Route::patch(
        'roles/{role}',
        [UserManagementController::class, 'rolesUpdate']
    )->middleware('permission:user-management.update');

    Route::delete(
        'roles/{role}',
        [UserManagementController::class, 'rolesDestroy']
    )->middleware('permission:user-management.delete');


    // PERMISSIONS
    Route::get(
        'permissions',
        [UserManagementController::class, 'permissionsIndex']
    )->middleware('permission:user-management.view');

    Route::post(
        'permissions',
        [UserManagementController::class, 'permissionsStore']
    )->middleware('permission:user-management.create');

    Route::put(
        'permissions/sync',
        [UserManagementController::class, 'permissionsSync']
    )->middleware('permission:user-management.update');

    Route::get(
        'permissions/{permission}',
        [UserManagementController::class, 'permissionsShow']
    )->middleware('permission:user-management.view');

    Route::put(
        'permissions/{permission}',
        [UserManagementController::class, 'permissionsUpdate']
    )->middleware('permission:user-management.update');

    Route::patch(
        'permissions/{permission}',
        [UserManagementController::class, 'permissionsUpdate']
    )->middleware('permission:user-management.update');

    Route::delete(
        'permissions/{permission}',
        [UserManagementController::class, 'permissionsDestroy']
    )->middleware('permission:user-management.delete');

});