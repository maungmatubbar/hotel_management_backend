<?php

namespace App\Domain\Billing\DTO;

class BookingPaymentRedirectData
{
    public function __construct(
        public readonly string $payment_url,
        public readonly string $transaction_id,
    ) {}
}
