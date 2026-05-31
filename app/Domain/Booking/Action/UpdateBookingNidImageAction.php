<?php

namespace App\Domain\Booking\Action;

use App\Models\Booking;
use App\Support\File\Contracts\UploadFileInterface;

class UpdateBookingNidImageAction
{
    public function __construct(private UploadFileInterface $uploadFile) {}

    public function __invoke(Booking $booking, string $tenantId, string $nidImageUrl): Booking
    {
        $existingFiles = $booking->files()
            ->where('category', Booking::NID_IMAGE_CATEGORY)
            ->get();

        $this->uploadFile->deleteMany($existingFiles);

        $this->uploadFile->createFromPath(
            fileable: $booking,
            pathOrUrl: $nidImageUrl,
            category: Booking::NID_IMAGE_CATEGORY,
            tenantId: $tenantId,
        );

        return $booking->refresh()->load([
            'files' => fn ($query) => $query->where('category', Booking::NID_IMAGE_CATEGORY),
        ]);
    }
}
