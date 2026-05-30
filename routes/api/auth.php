<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')
    ->name('auth.')
    ->group(function (): void {
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1')
            ->name('login');

        Route::get('me', [AuthController::class, 'me'])
            ->middleware('auth:sanctum')
            ->name('me');
    });

