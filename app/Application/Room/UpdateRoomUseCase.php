<?php

namespace App\Application\Room;

use App\Domain\Room\DTO\RoomUpdateDataRequest;
use App\Models\Room;
use App\Support\File\Contracts\UploadFileInterface;

class UpdateRoomUseCase
{
    public function __construct(private UploadFileInterface $uploadFile) {}

    public function __invoke(Room $room, RoomUpdateDataRequest $data): Room
    {
        $attributes = [];

        foreach (['room_name', 'room_type', 'capacity', 'rate', 'available_rooms', 'status', 'amenities', 'description'] as $field) {
            if ($data->{$field} !== null) {
                $attributes[$field] = $data->{$field};
            }
        }

        if ($attributes !== []) {
            $room->update($attributes);
        }

        if ($data->images !== null) {
            $existingFiles = $room->files()
                ->where('category', Room::IMAGE_CATEGORY)
                ->get();

            $this->uploadFile->deleteMany($existingFiles);

            $tenantId = (string) tenancy()->tenant?->getKey();

            foreach ($data->images as $image) {
                if (is_string($image)) {
                    $this->uploadFile->createFromPath(
                        fileable: $room,
                        pathOrUrl: $image,
                        category: Room::IMAGE_CATEGORY,
                        tenantId: $tenantId,
                    );
                }
            }
        }

        return $room->refresh()->load([
            'files' => fn ($query) => $query->where('category', Room::IMAGE_CATEGORY),
        ]);
    }
}
