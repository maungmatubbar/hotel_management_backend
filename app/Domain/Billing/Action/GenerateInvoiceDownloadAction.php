<?php

namespace App\Domain\Billing\Action;

use App\Models\Invoice;
use Spatie\LaravelPdf\PdfBuilder;

use function Spatie\LaravelPdf\Support\pdf;

class GenerateInvoiceDownloadAction
{
    public function __invoke(Invoice $invoice): PdfBuilder
    {
        $invoice->loadMissing(['booking.user', 'payments.receipt']);

        return pdf()
            ->view('pdf.billing.invoice', [
                'invoice' => $invoice,
            ])
            ->format('a4')
            ->name("{$invoice->invoice_number}.pdf")
            ->download();
    }
}
