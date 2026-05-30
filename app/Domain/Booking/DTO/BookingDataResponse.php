<?php

namespace App\Domain\Booking\DTO;

use App\Models\Booking;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class BookingDataResponse extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $tenant_id,
        public readonly int $user_id,
        public readonly ?int $room_id,
        public readonly string $guest_name,
        public readonly ?string $guest_phone,
        public readonly ?string $guest_email,
        public readonly ?string $guest_address,
        public readonly string $room,
        public readonly string $assigned_room_number,
        public readonly ?string $nid_number,
        public readonly ?string $nid_image_url,
        public readonly int $room_quantity,
        public readonly string $discount,
        public readonly ?string $promo_code,
        public readonly string $check_in,
        public readonly string $check_out,
        public readonly ?BookingInvoiceDataResponse $invoice = null,
    ) {}

    public static function fromBooking(Booking $booking): self
    {
        $booking->loadMissing([
            'user',
            'invoice.payments.receipt',
            'files' => fn ($query) => $query->where('category', Booking::NID_IMAGE_CATEGORY),
        ]);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        $nidImagePath = $booking->files->first()?->path;

        return new self(
            id: $booking->id,
            tenant_id: $booking->tenant_id !== null ? (string) $booking->tenant_id : null,
            user_id: $booking->user_id,
            room_id: $booking->room_id,
            guest_name: $booking->guest_name,
            guest_phone: $booking->guest_phone,
            guest_email: $booking->guest_email,
            guest_address: $booking->guest_address,
            room: $booking->room,
            assigned_room_number: $booking->assigned_room_number,
            nid_number: $booking->nid_number,
            nid_image_url: $nidImagePath ? $disk->url($nidImagePath) : null,
            room_quantity: $booking->room_quantity,
            discount: $booking->discount,
            promo_code: $booking->promo_code,
            check_in: $booking->check_in->toDateString(),
            check_out: $booking->check_out->toDateString(),
            invoice: $booking->invoice
                ? BookingInvoiceDataResponse::fromInvoice($booking->invoice)
                : null,
        );
    }
}
