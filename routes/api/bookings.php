<?php

use App\Http\Controllers\Tenant\BookingController;
use App\Http\Middleware\InitializeTenantByRouteParameter;
use App\Http\Middleware\InitializeTenantForPublicRoute;
use Illuminate\Support\Facades\Route;

Route::prefix('tenants/{tenant}')
    ->middleware(InitializeTenantForPublicRoute::class)
    ->name('tenants.')
    ->group(function (): void {
        Route::post('public-bookings', [BookingController::class, 'publicStore'])
            ->name('bookings.public.store');
    });

Route::prefix('tenants/{tenant}')
    ->middleware(['auth:sanctum', InitializeTenantByRouteParameter::class])
    ->name('tenants.')
    ->group(function (): void {
        Route::get('bookings', [BookingController::class, 'index'])
            ->name('bookings.index');

        Route::post('bookings', [BookingController::class, 'store'])
            ->name('bookings.store');

        Route::post('bookings/{booking}/payments', [BookingController::class, 'storeDownPayment'])
            ->name('bookings.down-payment.store');

        Route::post('bookings/{booking}/down-payment', [BookingController::class, 'storeDownPayment'])
            ->name('bookings.down-payment.alias.store');

        Route::get('bookings/{booking}/invoice/download', [BookingController::class, 'downloadInvoice'])
            ->name('bookings.invoice.download');

        Route::get('bookings/{booking}/receipts/{receipt}/download', [BookingController::class, 'downloadReceipt'])
            ->name('bookings.receipts.download');

        Route::patch('bookings/{booking}/status', [BookingController::class, 'updateStatus'])
            ->name('bookings.status.update');

        Route::get('bookings/{booking}', [BookingController::class, 'show'])
            ->name('bookings.show');
    });
