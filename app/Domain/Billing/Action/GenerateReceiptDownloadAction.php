<?php

namespace App\Domain\Billing\Action;

use App\Models\Receipt;
use Spatie\LaravelPdf\PdfBuilder;

use function Spatie\LaravelPdf\Support\pdf;

class GenerateReceiptDownloadAction
{
    public function __invoke(Receipt $receipt): PdfBuilder
    {
        $receipt->loadMissing('payment.invoice.booking');

        return pdf()
            ->view('pdf.billing.receipt', [
                'receipt' => $receipt,
            ])
            ->format('a4')
            ->name("{$receipt->receipt_number}.pdf")
            ->download();
    }
}
