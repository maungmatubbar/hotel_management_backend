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
    ) {}

    public static function fromReceipt(Receipt $receipt): self
    {
        return new self(
            id: $receipt->id,
            receipt_number: $receipt->receipt_number,
            issued_at: $receipt->issued_at->toDateTimeString(),
        );
    }
}
