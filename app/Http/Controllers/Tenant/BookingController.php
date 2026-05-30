<?php

namespace App\Http\Controllers\Tenant;

use App\Application\Booking\CreateBookingUseCase;
use App\Application\Booking\GetBookingsUseCase;
use App\Domain\Billing\Action\CreateBookingDownPaymentAction;
use App\Domain\Billing\Action\CreateReceiptForPaymentAction;
use App\Domain\Billing\Action\GenerateInvoiceDownloadAction;
use App\Domain\Billing\Action\GenerateReceiptDownloadAction;
use App\Domain\Booking\Action\UpdateBookingStatusAction;
use App\Domain\Booking\DTO\BookingDataRequest;
use App\Domain\Booking\DTO\BookingDataResponse;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListBookingsRequest;
use App\Http\Requests\StoreBookingDownPaymentRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingStatusRequest;
use App\Models\Booking;
use App\Models\Receipt;
use App\Models\Tenant;
use Illuminate\Contracts\Support\Responsable;
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

    public function storeDownPayment(
        StoreBookingDownPaymentRequest $request,
        string $tenant,
        int $booking,
        CreateBookingDownPaymentAction $createBookingDownPaymentAction,
        CreateReceiptForPaymentAction $createReceiptForPaymentAction,
    ): JsonResponse {
        $booking = $this->findBooking($booking);
        abort_unless($booking->invoice !== null, Response::HTTP_NOT_FOUND);

        $payment = $createBookingDownPaymentAction(
            invoice: $booking->invoice,
            amount: (string) $request->validated('amount'),
            method: (string) $request->validated('method'),
            reference: $request->validated('reference'),
            paidAt: $request->validated('paid_at'),
        );
        $createReceiptForPaymentAction($payment);

        return $this->successResponse(
            data: BookingDataResponse::fromBooking($booking->refresh()),
            status: Response::HTTP_CREATED,
        );
    }

    public function downloadInvoice(
        string $tenant,
        int $booking,
        GenerateInvoiceDownloadAction $generateInvoiceDownloadAction,
    ): Responsable {
        $booking = $this->findBooking($booking);
        abort_unless($booking->invoice !== null, Response::HTTP_NOT_FOUND);

        return $generateInvoiceDownloadAction($booking->invoice);
    }

    public function downloadReceipt(
        string $tenant,
        int $booking,
        int $receipt,
        GenerateReceiptDownloadAction $generateReceiptDownloadAction,
    ): Responsable {
        $booking = $this->findBooking($booking);
        $receipt = $this->findBookingReceipt($booking, $receipt);

        return $generateReceiptDownloadAction($receipt);
    }

    public function updateStatus(
        UpdateBookingStatusRequest $request,
        string $tenant,
        int $booking,
        UpdateBookingStatusAction $updateBookingStatusAction,
    ): JsonResponse {
        return $this->successResponse(
            data: BookingDataResponse::fromBooking(
                $updateBookingStatusAction(
                    $this->findBooking($booking),
                    BookingStatus::from($request->string('status')->toString()),
                ),
            ),
        );
    }

    private function findBooking(int $booking): Booking
    {
        return Booking::query()
            ->with([
                'user',
                'invoice.booking',
                'invoice.payments.receipt',
                'files' => fn ($query) => $query->where('category', Booking::NID_IMAGE_CATEGORY),
            ])
            ->findOrFail($booking);
    }

    private function findBookingReceipt(Booking $booking, int $receipt): Receipt
    {
        return Receipt::query()
            ->whereKey($receipt)
            ->whereHas('payment.invoice', fn ($query) => $query->whereKey($booking->invoice?->id))
            ->firstOrFail();
    }
}
