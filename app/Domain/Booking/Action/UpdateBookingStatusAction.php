<?php

namespace App\Domain\Booking\Action;

use App\Enums\BookingStatus;
use App\Models\Booking;

class UpdateBookingStatusAction
{
    public function __invoke(Booking $booking, BookingStatus $status): Booking
    {
        $booking->update([
            'status' => $status,
        ]);

        return $booking->refresh();
    }
}
