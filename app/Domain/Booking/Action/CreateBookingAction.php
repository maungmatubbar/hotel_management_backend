<?php

namespace App\Domain\Booking\Action;

use App\Domain\Booking\DTO\BookingDataRequest;
use App\Domain\Booking\Repositories\BookingRepositoryInterface;
use App\Domain\Room\Repositories\RoomRepositoryInterface;
use App\Models\Booking;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CreateBookingAction
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepository,
        private RoomRepositoryInterface $roomRepository,
        private StoreBookingNidImageAction $storeBookingNidImage,
    ) {}

    public function __invoke(Tenant $tenant, User $user, BookingDataRequest $data): Booking
    {
        $room = $this->roomRepository->findForTenant((string) $tenant->getKey(), $data->room_id);

        if ($room === null) {
            throw ValidationException::withMessages([
                'room_id' => __('validation.exists', ['attribute' => 'room']),
            ]);
        }

        $booking = $this->bookingRepository->create([
            'tenant_id' => $tenant->getKey(),
            'user_id' => $user->id,
            'room_id' => $room->id,
            'guest_name' => $data->guest_name,
            'guest_phone' => $data->guest_phone,
            'guest_email' => $data->guest_email,
            'guest_address' => $data->guest_address,
            'room' => $room->room_name,
            'assigned_room_number' => $data->assigned_room_number,
            'nid_number' => $data->nid_number,
            'room_quantity' => $data->room_quantity,
            'discount' => $data->discount,
            'promo_code' => $data->promo_code,
            'check_in' => $data->check_in,
            'check_out' => $data->check_out,
        ]);

        ($this->storeBookingNidImage)($booking, $tenant, $data);

        return $booking;
    }
}
