<?php

namespace App\Domain\Billing\Action;

use App\Domain\Booking\DTO\BookingDataRequest;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Validation\ValidationException;

class CreateBookingDownPaymentAction
{
    public function __invoke(Invoice $invoice, BookingDataRequest $data): Payment
    {
        if ($data->payment_method === null || $data->payment_method === '') {
            throw ValidationException::withMessages([
                'payment_method' => __('validation.required', ['attribute' => 'payment method']),
            ]);
        }

        $amount = (float) $data->down_payment_amount;

        if ($amount > (float) $invoice->amount_due) {
            throw ValidationException::withMessages([
                'down_payment_amount' => __('validation.lte.numeric', [
                    'attribute' => 'down payment amount',
                    'value' => $invoice->amount_due,
                ]),
            ]);
        }

        $payment = Payment::query()->create([
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'type' => Payment::TYPE_DOWN_PAYMENT,
            'method' => $data->payment_method,
            'reference' => $data->payment_reference,
            'paid_at' => $data->paid_at ?? now(),
        ]);

        $amountPaid = (float) $invoice->amount_paid + $amount;
        $amountDue = (float) $invoice->total_amount - $amountPaid;

        $invoice->update([
            'amount_paid' => $amountPaid,
            'amount_due' => max($amountDue, 0),
            'status' => $amountDue <= 0 ? Invoice::STATUS_PAID : Invoice::STATUS_PARTIAL,
        ]);

        return $payment;
    }
}
