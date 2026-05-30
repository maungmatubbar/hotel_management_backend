<?php

use App\Http\Controllers\Tenant\FileUploadController;
use App\Http\Middleware\InitializeTenantByRouteParameter;
use App\Http\Middleware\InitializeTenantFromAuthenticatedUser;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', InitializeTenantFromAuthenticatedUser::class])
    ->post('files/upload', [FileUploadController::class, 'store'])
    ->name('files.upload');

Route::prefix('tenants/{tenant}')
    ->middleware(['auth:sanctum', InitializeTenantByRouteParameter::class])
    ->name('tenants.')
    ->group(function (): void {
        Route::post('files/upload', [FileUploadController::class, 'store'])
            ->name('files.upload');
    });
