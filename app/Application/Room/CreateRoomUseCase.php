<?php

namespace App\Application\Room;

use App\Domain\Room\Action\CreateRoomAction;
use App\Domain\Room\DTO\RoomDataRequest;
use App\Models\Room;
use App\Support\File\Contracts\UploadFileInterface;

class CreateRoomUseCase
{
    public function __construct(
        private CreateRoomAction $createRoomAction,
        private UploadFileInterface $uploadFile,
    ) {}

    public function __invoke(RoomDataRequest $data): Room
    {
        $room = ($this->createRoomAction)($data);
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

        return $room->load([
            'files' => fn ($query) => $query->where('category', Room::IMAGE_CATEGORY),
        ]);
    }
}
