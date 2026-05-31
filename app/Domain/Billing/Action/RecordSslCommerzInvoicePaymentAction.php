<?php

namespace App\Domain\Billing\Action;

use App\Http\Requests\StoreSslCommerzInvoicePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;

class RecordSslCommerzInvoicePaymentAction
{
    public function __construct(
        private CreateBookingDownPaymentAction $createBookingDownPaymentAction,
        private CreateReceiptForPaymentAction $createReceiptForPaymentAction,
    ) {}

    public function __invoke(Invoice $invoice, StoreSslCommerzInvoicePaymentRequest $request): Invoice
    {
        $payment = $this->findExistingPayment($invoice, $request->reference())
            ?? ($this->createBookingDownPaymentAction)(
                invoice: $invoice,
                amount: $request->amount(),
                method: $request->method(),
                reference: $request->reference(),
                paidAt: $request->paidAt(),
            );

        if ($payment->receipt === null) {
            ($this->createReceiptForPaymentAction)($payment);
        }

        return $invoice->refresh();
    }

    private function findExistingPayment(Invoice $invoice, string $reference): ?Payment
    {
        return Payment::query()
            ->with('receipt')
            ->where('invoice_id', $invoice->id)
            ->where('reference', $reference)
            ->first();
    }
}
