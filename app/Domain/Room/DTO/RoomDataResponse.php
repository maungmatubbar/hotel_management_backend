<?php

namespace App\Domain\Room\DTO;

use App\Models\Room;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class RoomDataResponse extends Data
{
    /**
     * @param  array<int, string>  $amenities
     * @param  array<int, string>  $images
     * @param  array<int, string>  $image_urls
     */
    public function __construct(
        public readonly int $id,
        public readonly ?string $tenant_id,
        public readonly string $room_name,
        public readonly string $room_type,
        public readonly int $capacity,
        public readonly string $rate,
        public readonly int $available_rooms,
        public readonly string $status,
        public readonly array $amenities,
        public readonly array $images,
        public readonly array $image_urls,
        public readonly ?string $description,
    ) {}

    public static function fromRoom(Room $room): self
    {
        $room->loadMissing([
            'files' => fn ($query) => $query->where('category', Room::IMAGE_CATEGORY),
        ]);

        $images = $room->files
            ->pluck('path')
            ->values()
            ->all();

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return new self(
            id: $room->id,
            tenant_id: $room->tenant_id !== null ? (string) $room->tenant_id : null,
            room_name: $room->room_name,
            room_type: $room->room_type,
            capacity: $room->capacity,
            rate: $room->rate,
            available_rooms: $room->available_rooms,
            status: $room->status,
            amenities: $room->amenities ?? [],
            images: $images,
            image_urls: collect($images)
                ->map(fn (string $image): string => $disk->url($image))
                ->values()
                ->all(),
            description: $room->description,
        );
    }
}
