<?php

namespace App\Domain\Room\Repositories;

use App\Models\Room;

interface RoomRepositoryInterface
{
    public function findForTenant(string $tenantId, int $id): ?Room;

    /**
     * @param  array{
     *     room_name: string,
     *     room_type: string,
     *     capacity: int,
     *     rate: string,
     *     available_rooms: int,
     *     status: string,
     *     amenities: array<int, string>,
     *     description: ?string
     * }  $attributes
     */
    public function create(array $attributes): Room;
}
