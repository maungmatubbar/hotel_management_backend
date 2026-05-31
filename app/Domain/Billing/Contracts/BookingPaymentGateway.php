<?php

namespace App\Domain\Billing\Contracts;

use App\Domain\Billing\DTO\BookingPaymentRedirectData;
use App\Models\Booking;

interface BookingPaymentGateway
{
    public function redirectForBooking(Booking $booking): BookingPaymentRedirectData;
}
