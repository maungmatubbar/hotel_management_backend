<?php

namespace App\Domain\Room\Action;

use App\Domain\Room\DTO\RoomDataRequest;
use App\Domain\Room\Repositories\RoomRepositoryInterface;
use App\Models\Room;

class CreateRoomAction
{
    public function __construct(private RoomRepositoryInterface $roomRepository) {}

    public function __invoke(RoomDataRequest $data): Room
    {
        return $this->roomRepository->create([
            'room_name' => $data->room_name,
            'room_type' => $data->room_type,
            'capacity' => $data->capacity,
            'rate' => $data->rate,
            'available_rooms' => $data->available_rooms,
            'status' => $data->status ?: 'available',
            'amenities' => $data->amenities,
            'description' => $data->description,
        ]);
    }
}
