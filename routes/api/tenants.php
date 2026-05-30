<?php

use App\Http\Controllers\Tenant\TenantAuthController;
use App\Http\Controllers\Tenant\TenantController;
use App\Http\Middleware\InitializeTenantByRouteParameter;
use Illuminate\Support\Facades\Route;

Route::post('tenants/{tenant}/login', [TenantAuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('tenants.login');

Route::middleware('auth:sanctum')
    ->group(function (): void {
        Route::get('tenants', [TenantController::class, 'index'])
            ->name('tenants.index');

        Route::post('tenants', [TenantController::class, 'store'])
            ->name('tenants.store');

        Route::get('tenants/{tenant}/users', [TenantController::class, 'users'])
            ->name('tenants.users.index');

        Route::post('tenants/{tenant}/users', [TenantController::class, 'storeUser'])
            ->name('tenants.users.store');
    });

Route::prefix('tenants/{tenant}')
    ->middleware(['auth:sanctum', InitializeTenantByRouteParameter::class])
    ->name('tenants.')
    ->group(function (): void {
        Route::get('me', [TenantAuthController::class, 'me'])
            ->name('me');
    });
