<?php

use App\Http\Controllers\Tenant\BookingController;
use App\Http\Middleware\InitializeTenantByRouteParameter;
use Illuminate\Support\Facades\Route;

Route::prefix('tenants/{tenant}')
    ->middleware(['auth:sanctum', InitializeTenantByRouteParameter::class])
    ->name('tenants.')
    ->group(function (): void {
        Route::get('bookings', [BookingController::class, 'index'])
            ->name('bookings.index');

        Route::post('bookings', [BookingController::class, 'store'])
            ->name('bookings.store');

        Route::get('bookings/{booking}', [BookingController::class, 'show'])
            ->name('bookings.show');
    });
