<?php

namespace App\Domain\Billing\Action;

use App\Models\Payment;
use App\Models\Receipt;

class CreateReceiptForPaymentAction
{
    public function __invoke(Payment $payment): Receipt
    {
        return Receipt::query()->create([
            'payment_id' => $payment->id,
            'receipt_number' => $this->generateReceiptNumber($payment),
            'issued_at' => $payment->paid_at,
        ]);
    }

    private function generateReceiptNumber(Payment $payment): string
    {
        return 'RCP-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
    }
}
