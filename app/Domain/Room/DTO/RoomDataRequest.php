<?php

namespace App\Domain\Room\DTO;

use Spatie\LaravelData\Data;

class RoomDataRequest extends Data
{
    /**
     * @param  array<int, string>  $amenities
     * @param  array<int, string>  $images
     */
    public function __construct(
        public readonly string $room_name,
        public readonly string $room_type,
        public readonly int $capacity,
        public readonly string $rate,
        public readonly int $available_rooms,
        public readonly ?string $status = 'available',
        public readonly array $amenities = [],
        public readonly array $images = [],
        public readonly ?string $description = null,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'room_name' => ['required', 'string', 'max:255'],
            'room_type' => ['required', 'string', 'in:ac,non_ac'],
            'capacity' => ['required', 'integer', 'min:1'],
            'rate' => ['required', 'numeric', 'min:0'],
            'available_rooms' => ['required', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'max:50'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['required', 'string', 'max:255'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['required', 'string', 'max:2048'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
