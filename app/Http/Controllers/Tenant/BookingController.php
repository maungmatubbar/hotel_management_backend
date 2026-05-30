<?php

namespace App\Http\Controllers\Tenant;

use App\Application\Booking\CreateBookingUseCase;
use App\Application\Booking\GetBookingsUseCase;
use App\Domain\Booking\DTO\BookingDataRequest;
use App\Domain\Booking\DTO\BookingDataResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListBookingsRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BookingController extends Controller
{
    public function index(ListBookingsRequest $request, GetBookingsUseCase $getBookingsUseCase): JsonResponse
    {
        return $this->successResponse(
            data: $getBookingsUseCase($request->integer('per_page', 15))
                ->through(fn (Booking $booking): BookingDataResponse => BookingDataResponse::fromBooking($booking)),
        );
    }

    public function store(StoreBookingRequest $request, Tenant $tenant, CreateBookingUseCase $createBookingUseCase): JsonResponse
    {
        return $this->successResponse(
            data: BookingDataResponse::fromBooking($createBookingUseCase($tenant, BookingDataRequest::from($request->validated()))),
            status: Response::HTTP_CREATED,
        );
    }

    public function show(string $tenant, int $booking): JsonResponse
    {
        return $this->successResponse(
            data: BookingDataResponse::fromBooking($this->findBooking($booking)),
        );
    }

    private function findBooking(int $booking): Booking
    {
        return Booking::query()
            ->with([
                'user',
                'invoice.payments.receipt',
                'files' => fn ($query) => $query->where('category', Booking::NID_IMAGE_CATEGORY),
            ])
            ->findOrFail($booking);
    }
}
