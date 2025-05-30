<?php

use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\OemController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\TypeController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

// Public authentication routes
Route::prefix('auth')->name('api.v1.auth.')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('register', [AuthController::class, 'register'])->name('register');
});

// Authentication routes (authenticated users only)
Route::middleware(['auth:api'])->prefix('auth')->name('api.v1.auth.')->group(function () {
    Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
    Route::get('me', [AuthController::class, 'me'])->name('me');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::put('password', [AuthController::class, 'changePassword'])->name('password');
});

// Protected API routes (JWT auth + organization middleware required)
Route::middleware(['auth:api', 'ensure.organization'])->name('api.v1.')->group(function () {

    // Organization Management
    Route::prefix('organization')->name('organization.')->group(function () {
        Route::get('/', [OrganizationController::class, 'show'])
            ->middleware('permission:view-organization')
            ->name('show');
        Route::get('overview', [OrganizationController::class, 'overview'])
            ->middleware('permission:view-organization-statistics')
            ->name('overview');
        Route::get('statistics', [OrganizationController::class, 'statistics'])
            ->middleware('permission:view-organization-statistics')
            ->name('statistics');
        Route::get('health', [OrganizationController::class, 'health'])
            ->middleware('permission:view-organization-health')
            ->name('health');
    });

    Route::middleware(['role:super-admin,admin'])->group(function () {
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('roles', [UserController::class, 'roles'])->name('roles');
            Route::get('permissions', [UserController::class, 'permissions'])->name('permissions');
        });
        Route::apiResource('users', UserController::class)->names('users');
    });

    // Asset Management Routes
    Route::prefix('assets')->name('assets.')->group(function () {
        Route::get('warranty/expiring-soon', [AssetController::class, 'warrantyExpiringSoon'])
            ->name('warranty.expiring-soon')
            ->middleware('permission:view-asset-statistics');
        Route::get('warranty/expired', [AssetController::class, 'warrantyExpired'])
            ->name('warranty.expired')
            ->middleware('permission:view-asset-statistics');
        Route::get('statistics', [AssetController::class, 'statistics'])
            ->middleware('permission:view-asset-statistics')
            ->name('statistics');
        Route::get('search', [AssetController::class, 'search'])
            ->middleware('permission:view-assets')
            ->name('search');
        Route::get('incomplete-data', [AssetController::class, 'incompleteData'])
            ->middleware('permission:view-asset-statistics')
            ->name('incomplete-data');
        Route::post('bulk/delete', [AssetController::class, 'bulkDelete'])
            ->middleware('permission:delete-assets')
            ->name('bulk.delete');
        Route::post('bulk/update', [AssetController::class, 'bulkUpdate'])
            ->middleware('permission:bulk-update-assets')
            ->name('bulk.update');
    });

    Route::apiResource('assets', AssetController::class)->names('assets');

    Route::prefix('assets')->name('assets.')->group(function () {
        Route::get('{asset}/warranties', [AssetController::class, 'warranties'])
            ->middleware('permission:manage-asset-warranties')
            ->name('warranties');
        Route::post('{id}/restore', [AssetController::class, 'restore'])
            ->name('restore')
            ->middleware('permission:delete-assets');
        Route::delete('{id}/force', [AssetController::class, 'forceDestroy'])
            ->name('force-destroy')
            ->middleware('permission:delete-assets');
        Route::post('{asset}/retire', [AssetController::class, 'retire'])
            ->middleware('permission:retire-assets')
            ->name('retire');
        Route::patch('{asset}/retire', [AssetController::class, 'retire'])
            ->name('retire.patch')
            ->middleware('permission:retire-assets');
        Route::post('{asset}/reactivate', [AssetController::class, 'reactivate'])
            ->middleware('permission:reactivate-assets')
            ->name('reactivate');
        Route::patch('{asset}/reactivate', [AssetController::class, 'reactivate'])
            ->name('reactivate.patch')
            ->middleware('permission:reactivate-assets');
        Route::patch('{asset}/calculate-quality', [AssetController::class, 'calculateDataQuality'])
            ->name('calculate-quality')
            ->middleware('permission:edit-assets');
        Route::post('{asset}/tags', [AssetController::class, 'attachTags'])
            ->name('attach-tags')
            ->middleware('permission:edit-assets');
        Route::delete('{asset}/tags', [AssetController::class, 'detachTags'])
            ->name('detach-tags')
            ->middleware('permission:edit-assets');
    });

    // Customer Management Routes
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('incomplete-data', [CustomerController::class, 'incompleteData'])
            ->name('incomplete-data')
            ->middleware('permission:view-customer-statistics');
        Route::get('statistics', [CustomerController::class, 'statistics'])
            ->name('statistics')
            ->middleware('permission:view-customer-statistics');
        Route::get('search', [CustomerController::class, 'search'])
            ->name('search')
            ->middleware('permission:view-customers');
        Route::post('bulk/activate', [CustomerController::class, 'bulkActivate'])
            ->name('bulk.activate')
            ->middleware('permission:activate-customers');
        Route::post('bulk/deactivate', [CustomerController::class, 'bulkDeactivate'])
            ->name('bulk.deactivate')
            ->middleware('permission:deactivate-customers');
        Route::post('bulk/delete', [CustomerController::class, 'bulkDelete'])
            ->middleware('permission:delete-customers')
            ->name('bulk.delete');
        Route::post('bulk/update', [CustomerController::class, 'bulkUpdate'])
            ->middleware('permission:bulk-update-customers')
            ->name('bulk.update');
    });

    Route::apiResource('customers', CustomerController::class)->names('customers');

    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('{customer}/assets', [CustomerController::class, 'assets'])
            ->middleware('permission:view-assets')
            ->name('assets.index');
        Route::get('{customer}/locations', [CustomerController::class, 'locations'])
            ->middleware('permission:view-locations')
            ->name('locations.index');
        Route::post('{id}/restore', [CustomerController::class, 'restore'])
            ->name('restore')
            ->middleware('permission:delete-customers');
        Route::delete('{id}/force', [CustomerController::class, 'forceDestroy'])
            ->name('force-destroy')
            ->middleware('permission:delete-customers');
        Route::post('{customer}/activate', [CustomerController::class, 'activate'])
            ->middleware('permission:activate-customers')
            ->name('activate');
        Route::patch('{customer}/activate', [CustomerController::class, 'activate'])
            ->name('activate.patch')
            ->middleware('permission:activate-customers');
        Route::post('{customer}/deactivate', [CustomerController::class, 'deactivate'])
            ->middleware('permission:deactivate-customers')
            ->name('deactivate');
        Route::patch('{customer}/deactivate', [CustomerController::class, 'deactivate'])
            ->name('deactivate.patch')
            ->middleware('permission:deactivate-customers');
    });

    // Lookup Management Routes
    // OEM (Original Equipment Manufacturer) Management
    Route::apiResource('oems', OemController::class)->names('oems');

    // Product Management
    Route::apiResource('products', ProductController::class)->names('products');

    // Asset Type Management
    Route::apiResource('types', TypeController::class)->names('types');

    // Tag Management
    Route::apiResource('tags', TagController::class)->names('tags');
    Route::prefix('tags')->name('tags.')->group(function () {
        Route::post('{tag}/assets', [TagController::class, 'attachToAssets'])
            ->name('attach-assets')
            ->middleware('permission:edit-assets');
        Route::delete('{tag}/assets', [TagController::class, 'detachFromAssets'])
            ->name('detach-assets')
            ->middleware('permission:edit-assets');
    });

    // Location Management
    Route::apiResource('locations', LocationController::class)->names('locations');
    Route::prefix('locations')->name('locations.')->group(function () {
        Route::get('{location}/assets', [LocationController::class, 'assets'])
            ->name('assets')
            ->middleware('permission:view-assets');
    });
});
