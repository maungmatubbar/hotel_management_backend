<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Booking\Repositories\BookingRepositoryInterface;
use App\Models\Booking;

class BookingRepository implements BookingRepositoryInterface
{
    /**
     * @param  array{
     *     tenant_id: mixed,
     *     user_id: int,
     *     room_id: int,
     *     guest_name: string,
     *     guest_phone: ?string,
     *     guest_email: ?string,
     *     guest_address: ?string,
     *     room: string,
     *     assigned_room_number: string,
     *     nid_number: ?string,
     *     room_quantity: int,
     *     discount: mixed,
     *     promo_code: ?string,
     *     check_in: mixed,
     *     check_out: mixed,
     *     status: string
     * }  $attributes
     */
    public function create(array $attributes): Booking
    {
        return Booking::query()->create($attributes);
    }
}
