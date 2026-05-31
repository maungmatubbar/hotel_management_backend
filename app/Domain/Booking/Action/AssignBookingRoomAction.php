<?php

namespace App\Domain\Booking\Action;

use App\Domain\Room\Repositories\RoomRepositoryInterface;
use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Validation\ValidationException;

class AssignBookingRoomAction
{
    public function __construct(
        private RoomRepositoryInterface $roomRepository,
    ) {}

    public function __invoke(Booking $booking, string $tenantId, int $roomId, string $assignedRoomNumber): Booking
    {
        if ((string) $booking->tenant_id !== $tenantId) {
            throw ValidationException::withMessages([
                'booking' => __('validation.exists', ['attribute' => 'booking']),
            ]);
        }

        $room = $this->roomRepository->findForTenant($tenantId, $roomId);

        if ($room === null) {
            throw ValidationException::withMessages([
                'room_id' => __('validation.exists', ['attribute' => 'room']),
            ]);
        }

        $booking->update([
            'room_id' => $room->id,
            'room' => $room->room_name,
            'assigned_room_number' => $assignedRoomNumber,
            'status' => BookingStatus::CheckIn,
        ]);

        return $booking->refresh();
    }
}
