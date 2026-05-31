<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Billing\Action\RecordSslCommerzInvoicePaymentAction;
use App\Domain\Booking\DTO\BookingInvoiceDataResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSslCommerzInvoicePaymentRequest;
use App\Models\Invoice;
use App\Support\Payments\SslCommerz\SslCommerzUrlBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function show(string $tenant, int $invoice): JsonResponse
    {
        return $this->successResponse(
            data: BookingInvoiceDataResponse::fromInvoice($this->findPublicInvoiceById($tenant, $invoice)),
        );
    }

    public function showByNumber(string $tenant, string $invoiceNumber): JsonResponse
    {
        return $this->successResponse(
            data: BookingInvoiceDataResponse::fromInvoice($this->findPublicInvoiceByNumber($tenant, $invoiceNumber)),
        );
    }

    public function storeSslCommerzPayment(
        StoreSslCommerzInvoicePaymentRequest $request,
        string $tenant,
        int $invoice,
        RecordSslCommerzInvoicePaymentAction $recordSslCommerzInvoicePaymentAction,
    ): JsonResponse|RedirectResponse {
        return $this->handleSslCommerzPayment(
            $request,
            $recordSslCommerzInvoicePaymentAction,
            $this->findPublicInvoiceById($tenant, $invoice),
        );
    }

    public function storeSslCommerzPaymentByNumber(
        StoreSslCommerzInvoicePaymentRequest $request,
        string $tenant,
        string $invoiceNumber,
        RecordSslCommerzInvoicePaymentAction $recordSslCommerzInvoicePaymentAction,
    ): JsonResponse|RedirectResponse {
        return $this->handleSslCommerzPayment(
            $request,
            $recordSslCommerzInvoicePaymentAction,
            $this->findPublicInvoiceByNumber($tenant, $invoiceNumber),
        );
    }

    public function storeSslCommerzIpnByNumber(
        StoreSslCommerzInvoicePaymentRequest $request,
        string $tenant,
        string $invoiceNumber,
        RecordSslCommerzInvoicePaymentAction $recordSslCommerzInvoicePaymentAction,
    ): JsonResponse {
        abort_unless($request->isSuccessful(), Response::HTTP_UNPROCESSABLE_ENTITY, 'SSLCommerz payment was not successful.');

        $invoice = $recordSslCommerzInvoicePaymentAction(
            $this->findPublicInvoiceByNumber($tenant, $invoiceNumber),
            $request,
        );

        return $this->successResponse(
            data: BookingInvoiceDataResponse::fromInvoice($invoice),
        );
    }

    public function redirectSslCommerzFailByNumber(string $tenant, string $invoiceNumber): RedirectResponse
    {
        $invoice = $this->findPublicInvoiceByNumber($tenant, $invoiceNumber);
        $invoice->loadMissing('booking');

        return redirect()->away(SslCommerzUrlBuilder::fromTemplate(
            (string) config('services.sslcommerz.fail_url'),
            $invoice->booking,
        ));
    }

    public function redirectSslCommerzCancelByNumber(string $tenant, string $invoiceNumber): RedirectResponse
    {
        $invoice = $this->findPublicInvoiceByNumber($tenant, $invoiceNumber);
        $invoice->loadMissing('booking');

        return redirect()->away(SslCommerzUrlBuilder::fromTemplate(
            (string) config('services.sslcommerz.cancel_url'),
            $invoice->booking,
        ));
    }

    private function handleSslCommerzPayment(
        StoreSslCommerzInvoicePaymentRequest $request,
        RecordSslCommerzInvoicePaymentAction $recordSslCommerzInvoicePaymentAction,
        Invoice $invoice,
    ): JsonResponse|RedirectResponse {
        abort_unless($request->isSuccessful(), Response::HTTP_UNPROCESSABLE_ENTITY, 'SSLCommerz payment was not successful.');

        $invoice = $recordSslCommerzInvoicePaymentAction($invoice, $request);
        $invoiceData = BookingInvoiceDataResponse::fromInvoice($invoice);

        if ($request->wantsJson()) {
            return $this->successResponse(data: $invoiceData);
        }

        $invoice->loadMissing('booking');

        return redirect()->away(SslCommerzUrlBuilder::fromTemplate(
            (string) config('services.sslcommerz.success_url'),
            $invoice->booking,
        ));
    }

    private function findPublicInvoiceById(string $tenant, int $invoice): Invoice
    {
        return Invoice::query()
            ->with(['booking', 'payments.receipt'])
            ->whereKey($invoice)
            ->whereHas('booking', fn ($query) => $query->where('tenant_id', $tenant))
            ->firstOrFail();
    }

    private function findPublicInvoiceByNumber(string $tenant, string $invoiceNumber): Invoice
    {
        return Invoice::query()
            ->with(['booking', 'payments.receipt'])
            ->where('invoice_number', $invoiceNumber)
            ->whereHas('booking', fn ($query) => $query->where('tenant_id', $tenant))
            ->firstOrFail();
    }
}
