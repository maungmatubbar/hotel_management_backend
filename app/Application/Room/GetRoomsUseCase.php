<?php

namespace App\Application\Room;

use App\Models\Room;
use Illuminate\Database\Eloquent\Collection;

class GetRoomsUseCase
{
    /**
     * @return Collection<int, Room>
     */
    public function __invoke(): Collection
    {
        return Room::query()
            ->with([
                'files' => fn ($query) => $query->where('category', Room::IMAGE_CATEGORY),
            ])
            ->latest('id')
            ->get();
    }
}
