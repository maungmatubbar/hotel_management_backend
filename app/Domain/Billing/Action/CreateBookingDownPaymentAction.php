<?php

namespace App\Domain\Billing\Action;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CreateBookingDownPaymentAction
{
    public function __invoke(
        Invoice $invoice,
        string $amount,
        string $method,
        ?string $reference = null,
        ?string $paidAt = null,
    ): Payment {
        if ($method === '') {
            throw ValidationException::withMessages([
                'payment_method' => __('validation.required', ['attribute' => 'payment method']),
            ]);
        }

        $paymentAmount = (float) $amount;

        if ($paymentAmount > (float) $invoice->amount_due) {
            throw ValidationException::withMessages([
                'amount' => __('validation.lte.numeric', [
                    'attribute' => 'down payment amount',
                    'value' => $invoice->amount_due,
                ]),
            ]);
        }

        $currentTimestamp = now();
        $paidAtTimestamp = $paidAt !== null ? Carbon::parse($paidAt) : $currentTimestamp;

        if ($paidAt !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $paidAt) === 1) {
            $paidAtTimestamp->setTime(
                $currentTimestamp->hour,
                $currentTimestamp->minute,
                $currentTimestamp->second,
            );
        }

        $payment = Payment::query()->create([
            'invoice_id' => $invoice->id,
            'amount' => $paymentAmount,
            'type' => Payment::TYPE_DOWN_PAYMENT,
            'method' => $method,
            'reference' => $reference,
            'paid_at' => $paidAtTimestamp,
        ]);

        $amountPaid = (float) $invoice->amount_paid + $paymentAmount;
        $amountDue = (float) $invoice->total_amount - $amountPaid;

        $invoice->update([
            'amount_paid' => $amountPaid,
            'amount_due' => max($amountDue, 0),
            'status' => $amountDue <= 0 ? Invoice::STATUS_PAID : Invoice::STATUS_PARTIAL,
        ]);

        return $payment;
    }
}
