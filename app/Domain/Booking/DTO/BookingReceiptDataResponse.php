<?php

namespace App\Domain\Booking\DTO;

use App\Models\Receipt;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class BookingReceiptDataResponse extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $receipt_number,
        public readonly string $issued_at,
        public readonly string $download_url,
    ) {}

    public static function fromReceipt(Receipt $receipt): self
    {
        $receipt->loadMissing('payment.invoice.booking');

        return new self(
            id: $receipt->id,
            receipt_number: $receipt->receipt_number,
            issued_at: $receipt->issued_at->toDateTimeString(),
            download_url: route('tenants.bookings.receipts.download', [
                'tenant' => $receipt->payment->invoice->booking->tenant_id,
                'booking' => $receipt->payment->invoice->booking_id,
                'receipt' => $receipt->id,
            ]),
        );
    }
}
