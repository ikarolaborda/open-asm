<?php

use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

// Authentication routes (authenticated users only)
Route::middleware(['auth:api'])->prefix('auth')->group(function () {
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
});

// Protected API routes (JWT auth + organization middleware required)
Route::middleware(['auth:api', 'ensure.organization'])->group(function () {

    // Organization Management
    Route::prefix('organization')->group(function () {
        Route::get('/', [OrganizationController::class, 'show'])
            ->middleware('permission:view-organization');
        Route::get('overview', [OrganizationController::class, 'overview'])
            ->middleware('permission:view-organization-statistics');
        Route::get('statistics', [OrganizationController::class, 'statistics'])
            ->middleware('permission:view-organization-statistics');
        Route::get('health', [OrganizationController::class, 'health'])
            ->middleware('permission:view-organization-health');
    });

    Route::middleware(['role:super-admin,admin'])->group(function () {
        Route::prefix('users')->group(function () {
            Route::get('roles', [UserController::class, 'roles']);
            Route::get('permissions', [UserController::class, 'permissions']);
        });
        Route::apiResource('users', UserController::class);
    });

    // Asset Management Routes
    Route::prefix('assets')->group(function () {
        Route::get('warranty/expiring-soon', [AssetController::class, 'warrantyExpiringSoon'])
            ->name('assets.warranty.expiring-soon')
            ->middleware('permission:view-asset-statistics');
        Route::get('warranty/expired', [AssetController::class, 'warrantyExpired'])
            ->name('assets.warranty.expired')
            ->middleware('permission:view-asset-statistics');
        Route::get('statistics', [AssetController::class, 'statistics'])
            ->middleware('permission:view-asset-statistics');
        Route::get('search', [AssetController::class, 'search'])
            ->middleware('permission:view-assets');
        Route::get('incomplete-data', [AssetController::class, 'incompleteData'])
            ->middleware('permission:view-asset-statistics');
        Route::post('bulk/delete', [AssetController::class, 'bulkDelete'])
            ->middleware('permission:delete-assets');
        Route::post('bulk/update', [AssetController::class, 'bulkUpdate'])
            ->middleware('permission:bulk-update-assets');
    });

    Route::apiResource('assets', AssetController::class);

    Route::prefix('assets')->group(function () {
        Route::get('{asset}/warranties', [AssetController::class, 'warranties'])
            ->middleware('permission:manage-asset-warranties');
        Route::post('{id}/restore', [AssetController::class, 'restore'])
            ->name('assets.restore')
            ->middleware('permission:delete-assets');
        Route::delete('{id}/force', [AssetController::class, 'forceDestroy'])
            ->name('assets.force-destroy')
            ->middleware('permission:delete-assets');
        Route::post('{asset}/retire', [AssetController::class, 'retire'])
            ->middleware('permission:retire-assets');
        Route::patch('{asset}/retire', [AssetController::class, 'retire'])
            ->name('assets.retire')
            ->middleware('permission:retire-assets');
        Route::post('{asset}/reactivate', [AssetController::class, 'reactivate'])
            ->middleware('permission:reactivate-assets');
        Route::patch('{asset}/reactivate', [AssetController::class, 'reactivate'])
            ->name('assets.reactivate')
            ->middleware('permission:reactivate-assets');
        Route::patch('{asset}/calculate-quality', [AssetController::class, 'calculateDataQuality'])
            ->name('assets.calculate-quality')
            ->middleware('permission:edit-assets');
    });

    // Customer Management Routes
    Route::prefix('customers')->group(function () {
        Route::get('incomplete-data', [CustomerController::class, 'incompleteData'])
            ->name('customers.incomplete-data')
            ->middleware('permission:view-customer-statistics');
        Route::get('statistics', [CustomerController::class, 'statistics'])
            ->name('customers.statistics')
            ->middleware('permission:view-customer-statistics');
        Route::get('search', [CustomerController::class, 'search'])
            ->name('customers.search')
            ->middleware('permission:view-customers');
        Route::post('bulk/activate', [CustomerController::class, 'bulkActivate'])
            ->name('customers.bulk.activate')
            ->middleware('permission:activate-customers');
        Route::post('bulk/deactivate', [CustomerController::class, 'bulkDeactivate'])
            ->name('customers.bulk.deactivate')
            ->middleware('permission:deactivate-customers');
        Route::post('bulk/delete', [CustomerController::class, 'bulkDelete'])
            ->middleware('permission:delete-customers');
        Route::post('bulk/update', [CustomerController::class, 'bulkUpdate'])
            ->middleware('permission:bulk-update-customers');
    });

    Route::apiResource('customers', CustomerController::class);

    Route::prefix('customers')->group(function () {
        Route::get('{customer}/assets', [CustomerController::class, 'assets'])
            ->middleware('permission:view-assets');
        Route::get('{customer}/locations', [CustomerController::class, 'locations'])
            ->middleware('permission:view-locations');
        Route::post('{id}/restore', [CustomerController::class, 'restore'])
            ->name('customers.restore')
            ->middleware('permission:delete-customers');
        Route::delete('{id}/force', [CustomerController::class, 'forceDestroy'])
            ->name('customers.force-destroy')
            ->middleware('permission:delete-customers');
        Route::post('{customer}/activate', [CustomerController::class, 'activate'])
            ->middleware('permission:activate-customers');
        Route::patch('{customer}/activate', [CustomerController::class, 'activate'])
            ->name('customers.activate')
            ->middleware('permission:activate-customers');
        Route::post('{customer}/deactivate', [CustomerController::class, 'deactivate'])
            ->middleware('permission:deactivate-customers');
        Route::patch('{customer}/deactivate', [CustomerController::class, 'deactivate'])
            ->name('customers.deactivate')
            ->middleware('permission:deactivate-customers');
    });
});
