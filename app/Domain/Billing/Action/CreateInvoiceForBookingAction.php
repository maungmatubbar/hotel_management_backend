<?php

namespace App\Domain\Billing\Action;

use App\Models\Booking;
use App\Models\Invoice;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class CreateInvoiceForBookingAction
{
    public function __invoke(Booking $booking): Invoice
    {
        $room = $booking->bookedRoom()->first();

        if ($room === null) {
            throw ValidationException::withMessages([
                'room_id' => __('validation.exists', ['attribute' => 'room']),
            ]);
        }

        $nightCount = max(
            (int) CarbonImmutable::parse($booking->check_in)->diffInDays(CarbonImmutable::parse($booking->check_out)),
            1,
        );
        $subtotal = (float) $room->rate * $booking->room_quantity * $nightCount;
        $discount = (float) $booking->discount;
        $totalAmount = max($subtotal - $discount, 0);

        return Invoice::query()->create([
            'booking_id' => $booking->id,
            'invoice_number' => $this->generateInvoiceNumber($booking),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total_amount' => $totalAmount,
            'amount_paid' => 0,
            'amount_due' => $totalAmount,
            'status' => Invoice::STATUS_ISSUED,
            'issued_at' => now(),
            'due_at' => $booking->check_in,
        ]);
    }

    private function generateInvoiceNumber(Booking $booking): string
    {
        return 'INV-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT);
    }
}
