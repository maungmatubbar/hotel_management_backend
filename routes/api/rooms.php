<?php

use App\Http\Controllers\Tenant\RoomController;
use App\Http\Middleware\InitializeTenantByRouteParameter;
use Illuminate\Support\Facades\Route;

Route::prefix('tenants/{tenant}')
    ->middleware(['auth:sanctum', InitializeTenantByRouteParameter::class])
    ->name('tenants.')
    ->group(function (): void {
        Route::get('rooms', [RoomController::class, 'index'])
            ->name('rooms.index');

        Route::post('rooms', [RoomController::class, 'store'])
            ->name('rooms.store');

        Route::get('rooms/{room}', [RoomController::class, 'show'])
            ->name('rooms.show');

        Route::match(['put', 'patch'], 'rooms/{room}', [RoomController::class, 'update'])
            ->name('rooms.update');

        Route::delete('rooms/{room}', [RoomController::class, 'destroy'])
            ->name('rooms.destroy');
    });
