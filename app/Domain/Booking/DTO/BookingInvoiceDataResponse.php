<?php

namespace App\Domain\Booking\DTO;

use App\Models\Invoice;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class BookingInvoiceDataResponse extends Data
{
    /**
     * @param  array<int, BookingPaymentDataResponse>  $payments
     */
    public function __construct(
        public readonly int $id,
        public readonly string $invoice_number,
        public readonly string $subtotal,
        public readonly string $discount,
        public readonly string $total_amount,
        public readonly string $amount_paid,
        public readonly string $amount_due,
        public readonly string $status,
        public readonly string $issued_at,
        public readonly ?string $due_at,
        public readonly array $payments = [],
    ) {}

    public static function fromInvoice(Invoice $invoice): self
    {
        $invoice->loadMissing('payments.receipt');

        return new self(
            id: $invoice->id,
            invoice_number: $invoice->invoice_number,
            subtotal: $invoice->subtotal,
            discount: $invoice->discount,
            total_amount: $invoice->total_amount,
            amount_paid: $invoice->amount_paid,
            amount_due: $invoice->amount_due,
            status: $invoice->status,
            issued_at: $invoice->issued_at->toDateTimeString(),
            due_at: $invoice->due_at?->toDateTimeString(),
            payments: $invoice->payments
                ->map(fn ($payment): BookingPaymentDataResponse => BookingPaymentDataResponse::fromPayment($payment))
                ->values()
                ->all(),
        );
    }
}
