<?php

namespace App\Domain\Booking\DTO;

use App\Enums\BookingStatus;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class BookingDataRequest extends Data
{
    public function __construct(
        public readonly string $guest_name,
        public readonly ?string $guest_phone,
        public readonly ?string $guest_email,
        public readonly ?string $guest_address,
        public readonly int $room_id,
        public readonly string $assigned_room_number,
        public readonly ?string $nid_number,
        public readonly ?string $nid_image_url,
        public readonly int $room_quantity,
        public readonly string $discount,
        public readonly ?string $promo_code,
        public readonly string $check_in,
        public readonly string $check_out,
        public readonly ?string $room = null,
        public readonly ?string $status = null,
        public readonly ?string $down_payment_amount = null,
        public readonly ?string $payment_method = null,
        public readonly ?string $payment_reference = null,
        public readonly ?string $paid_at = null,
        public readonly ?int $user_id = null,
    ) {}

    public function hasDownPayment(): bool
    {
        return $this->down_payment_amount !== null && (float) $this->down_payment_amount > 0;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:255'],
            'guest_email' => ['required', 'email', 'max:255'],
            'guest_address' => ['nullable', 'string', 'max:1000'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'room' => ['nullable', 'string', 'max:255'],
            'assigned_room_number' => ['required', 'string', 'max:50'],
            'nid_number' => ['nullable', 'string', 'max:255'],
            'nid_image_url' => ['nullable', 'string', 'max:2048'],
            'room_quantity' => ['required', 'integer', 'min:1'],
            'discount' => ['required', 'numeric', 'min:0'],
            'promo_code' => ['nullable', 'string', 'max:255'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after_or_equal:check_in'],
            'status' => ['sometimes', 'string', Rule::enum(BookingStatus::class)],
            'down_payment_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['nullable', 'date'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
