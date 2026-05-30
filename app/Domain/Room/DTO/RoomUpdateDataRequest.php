<?php

namespace App\Domain\Room\DTO;

use Spatie\LaravelData\Data;

class RoomUpdateDataRequest extends Data
{
    /**
     * @param  array<int, string>  $amenities
     * @param  array<int, string>  $images
     */
    public function __construct(
        public readonly ?string $room_name = null,
        public readonly ?string $room_type = null,
        public readonly ?int $capacity = null,
        public readonly ?string $rate = null,
        public readonly ?int $available_rooms = null,
        public readonly ?string $status = null,
        public readonly ?array $amenities = null,
        public readonly ?array $images = null,
        public readonly ?string $description = null,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'room_name' => ['sometimes', 'required', 'string', 'max:255'],
            'room_type' => ['sometimes', 'required', 'string', 'in:ac,non_ac'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1'],
            'rate' => ['sometimes', 'required', 'numeric', 'min:0'],
            'available_rooms' => ['sometimes', 'required', 'integer', 'min:0'],
            'status' => ['sometimes', 'nullable', 'string', 'max:50'],
            'amenities' => ['sometimes', 'nullable', 'array'],
            'amenities.*' => ['required', 'string', 'max:255'],
            'images' => ['sometimes', 'nullable', 'array', 'max:10'],
            'images.*' => ['required', 'string', 'max:2048'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
