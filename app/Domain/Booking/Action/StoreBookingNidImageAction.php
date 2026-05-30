<?php

namespace App\Domain\Booking\Action;

use App\Domain\Booking\DTO\BookingDataRequest;
use App\Models\Booking;
use App\Models\Tenant;
use App\Support\File\Contracts\UploadFileInterface;

class StoreBookingNidImageAction
{
    public function __construct(private UploadFileInterface $uploadFile) {}

    public function __invoke(Booking $booking, Tenant $tenant, BookingDataRequest $data): void
    {
        if ($data->nid_image_url === null || $data->nid_image_url === '') {
            return;
        }

        $this->uploadFile->createFromPath(
            fileable: $booking,
            pathOrUrl: $data->nid_image_url,
            category: Booking::NID_IMAGE_CATEGORY,
            tenantId: (string) $tenant->getKey(),
        );
    }
}
