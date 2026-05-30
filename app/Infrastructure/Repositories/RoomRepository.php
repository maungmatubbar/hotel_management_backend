<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Room\Repositories\RoomRepositoryInterface;
use App\Models\Room;

class RoomRepository implements RoomRepositoryInterface
{
    public function findForTenant(string $tenantId, int $id): ?Room
    {
        return Room::query()
            ->whereKey($id)
            ->where('tenant_id', $tenantId)
            ->first();
    }

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
    public function create(array $attributes): Room
    {
        return Room::query()->create($attributes);
    }
}
