<?php

use App\Http\Controllers\Tenant\InvoiceController;
use App\Http\Middleware\InitializeTenantForPublicRoute;
use Illuminate\Support\Facades\Route;

Route::prefix('tenants/{tenant}/public')
    ->middleware(InitializeTenantForPublicRoute::class)
    ->name('tenants.')
    ->group(function (): void {
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])
            ->whereNumber('invoice')
            ->name('invoices.show');

        Route::get('invoices/{invoiceNumber}', [InvoiceController::class, 'showByNumber'])
            ->name('invoices.show.by-number.direct');

        Route::get('invoices/by-number/{invoiceNumber}', [InvoiceController::class, 'showByNumber'])
            ->name('invoices.show.by-number');

        Route::match(['get', 'post'], 'invoices/{invoice}/sslcommerz/success', [InvoiceController::class, 'storeSslCommerzPayment'])
            ->name('public.invoices.sslcommerz.success');

        Route::match(['get', 'post'], 'invoices/by-number/{invoiceNumber}/sslcommerz/success', [InvoiceController::class, 'storeSslCommerzPaymentByNumber'])
            ->name('public.invoices.sslcommerz.success.by-number');

        Route::match(['get', 'post'], 'invoices/by-number/{invoiceNumber}/sslcommerz/fail', [InvoiceController::class, 'redirectSslCommerzFailByNumber'])
            ->name('public.invoices.sslcommerz.fail.by-number');

        Route::match(['get', 'post'], 'invoices/by-number/{invoiceNumber}/sslcommerz/cancel', [InvoiceController::class, 'redirectSslCommerzCancelByNumber'])
            ->name('public.invoices.sslcommerz.cancel.by-number');

        Route::post('invoices/by-number/{invoiceNumber}/sslcommerz/ipn', [InvoiceController::class, 'storeSslCommerzIpnByNumber'])
            ->name('public.invoices.sslcommerz.ipn.by-number');
    });
