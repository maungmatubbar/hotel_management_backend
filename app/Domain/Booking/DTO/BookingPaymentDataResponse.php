<?php

namespace App\Domain\Booking\DTO;

use App\Models\Payment;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class BookingPaymentDataResponse extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $amount,
        public readonly string $type,
        public readonly string $method,
        public readonly ?string $reference,
        public readonly string $paid_at,
        public readonly ?BookingReceiptDataResponse $receipt,
    ) {}

    public static function fromPayment(Payment $payment): self
    {
        $payment->loadMissing('receipt');

        return new self(
            id: $payment->id,
            amount: $payment->amount,
            type: $payment->type,
            method: $payment->method,
            reference: $payment->reference,
            paid_at: $payment->paid_at->toDateTimeString(),
            receipt: $payment->receipt
                ? BookingReceiptDataResponse::fromReceipt($payment->receipt)
                : null,
        );
    }
}
