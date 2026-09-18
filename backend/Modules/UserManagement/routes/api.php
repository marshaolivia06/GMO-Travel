<?php

use Illuminate\Support\Facades\Route;
use Modules\UserManagement\Http\Controllers\UserController;
use Modules\UserManagement\Http\Controllers\RoleController;
use Modules\UserManagement\Http\Controllers\PermissionController;

Route::middleware(['auth:sanctum'])
    ->prefix('v1')
    ->group(function () {

        // USERS
        Route::apiResource('users', UserController::class)
            ->middlewareFor(['index', 'show'], 'permission:user-management.view')
            ->middlewareFor('store', 'permission:user-management.create')
            ->middlewareFor('update', 'permission:user-management.update')
            ->middlewareFor('destroy', 'permission:user-management.delete');

        // ROLES
        Route::apiResource('roles', RoleController::class)
            ->middlewareFor(['index', 'show'], 'permission:user-management.view')
            ->middlewareFor('store', 'permission:user-management.create')
            ->middlewareFor('update', 'permission:user-management.update')
            ->middlewareFor('destroy', 'permission:user-management.delete');

        // PERMISSIONS
        Route::put(
            'permissions/sync',
            [PermissionController::class, 'sync']
        )->middleware('permission:user-management.update');

        Route::apiResource('permissions', PermissionController::class)
            ->middlewareFor(['index', 'show'], 'permission:user-management.view')
            ->middlewareFor('store', 'permission:user-management.create')
            ->middlewareFor('update', 'permission:user-management.update')
            ->middlewareFor('destroy', 'permission:user-management.delete');
    });
