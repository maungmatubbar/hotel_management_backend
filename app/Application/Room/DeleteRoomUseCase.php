<?php

namespace App\Application\Room;

use App\Models\Room;
use App\Support\File\Contracts\UploadFileInterface;
use Illuminate\Database\Eloquent\Model;

class DeleteRoomUseCase
{
    public function __construct(private UploadFileInterface $uploadFile) {}

    public function __invoke(Room $room): void
    {
        $this->uploadFile->deleteMany(
            $room->files()->where('category', Room::IMAGE_CATEGORY)->get(),
        );

        /** @var Model $room */
        $room->delete();
    }
}
