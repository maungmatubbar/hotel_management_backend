<?php

namespace App\Observers;

use App\Models\Room;

class RoomObserver
{
    /**
     * Handle the Room "created" event.
     */
    public function created(Room $room): void
    {
        $this->assignTenant($room);
    }

    /**
     * Handle the Room "updated" event.
     */
    public function updated(Room $room): void
    {
        $this->assignTenant($room);
    }

    private function assignTenant(Room $room): void
    {
        $tenant = tenancy()->tenant;

        if ($tenant === null || (string) $room->tenant_id === (string) $tenant->getKey()) {
            return;
        }

        $room->tenant_id = (string) $tenant->getKey();
        $room->saveQuietly();
    }
}
