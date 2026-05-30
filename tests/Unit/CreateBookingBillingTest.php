<?php

use App\Application\Booking\CreateBookingUseCase;
use App\Domain\Billing\Action\CreateBookingDownPaymentAction;
use App\Domain\Billing\Action\CreateInvoiceForBookingAction;
use App\Domain\Billing\Action\CreateReceiptForPaymentAction;
use App\Domain\Booking\Action\CreateBookingAction;
use App\Domain\Booking\DTO\BookingDataRequest;
use App\Domain\Booking\DTO\BookingDataResponse;
use App\Models\Booking;
use App\Models\File;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    migrateTenantBookingSchema();
});

function migrateTenantBookingSchema(): void
{
    Schema::dropIfExists('receipts');
    Schema::dropIfExists('payments');
    Schema::dropIfExists('invoices');
    Schema::dropIfExists('bookings');

    (require database_path('migrations/2026_05_29_134947_create_bookings_table.php'))->up();
    (require database_path('migrations/2026_05_29_142311_create_invoices_table.php'))->up();
    (require database_path('migrations/2026_05_29_142312_create_payments_table.php'))->up();
    (require database_path('migrations/2026_05_29_142313_create_receipts_table.php'))->up();
}

function bookingDataRequest(array $overrides = []): BookingDataRequest
{
    $room = $overrides['room_record'] ?? createTenantRoom();

    return new BookingDataRequest(
        guest_name: $overrides['guest_name'] ?? 'Guest One',
        guest_phone: $overrides['guest_phone'] ?? '01700000000',
        guest_email: $overrides['guest_email'] ?? 'guest@example.com',
        guest_address: $overrides['guest_address'] ?? 'House 12, Road 3',
        room_id: $overrides['room_id'] ?? $room->id,
        assigned_room_number: $overrides['assigned_room_number'] ?? '301',
        nid_number: $overrides['nid_number'] ?? '1234567890',
        nid_image_url: $overrides['nid_image_url'] ?? null,
        room_quantity: $overrides['room_quantity'] ?? 1,
        discount: $overrides['discount'] ?? '0',
        promo_code: $overrides['promo_code'] ?? null,
        check_in: $overrides['check_in'] ?? '2026-06-01',
        check_out: $overrides['check_out'] ?? '2026-06-03',
        down_payment_amount: $overrides['down_payment_amount'] ?? null,
        payment_method: $overrides['payment_method'] ?? null,
        payment_reference: $overrides['payment_reference'] ?? null,
        paid_at: $overrides['paid_at'] ?? null,
        user_id: $overrides['user_id'] ?? null,
        room: $overrides['room'] ?? null,
    );
}

function createTenantRoom(array $overrides = []): Room
{
    return Room::factory()->create([
        'tenant_id' => $overrides['tenant_id'] ?? 'hotel-alpha',
        'room_name' => $overrides['room_name'] ?? 'Business Twin Room',
        'rate' => $overrides['rate'] ?? '625.25',
    ]);
}

function createTenantBooking(): Booking
{
    $room = createTenantRoom();

    return Booking::query()->create([
        'tenant_id' => 'hotel-alpha',
        'user_id' => 1,
        'room_id' => $room->id,
        'guest_name' => 'Guest One',
        'guest_phone' => '01700000000',
        'guest_email' => 'guest@example.com',
        'guest_address' => 'House 12, Road 3',
        'room' => $room->room_name,
        'assigned_room_number' => '301',
        'nid_number' => '1234567890',
        'room_quantity' => 1,
        'discount' => '0',
        'check_in' => '2026-06-01',
        'check_out' => '2026-06-03',
    ]);
}

test('create invoice for booking action creates an issued invoice', function () {
    $booking = createTenantBooking();

    $invoice = (new CreateInvoiceForBookingAction)($booking);

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->booking_id)->toBe($booking->id)
        ->and($invoice->invoice_number)->toBe('INV-000001')
        ->and($invoice->subtotal)->toBe('1250.50')
        ->and($invoice->total_amount)->toBe('1250.50')
        ->and($invoice->amount_paid)->toBe('0.00')
        ->and($invoice->amount_due)->toBe('1250.50')
        ->and($invoice->status)->toBe(Invoice::STATUS_ISSUED);
});

test('create booking down payment action records payment and updates invoice', function () {
    $booking = createTenantBooking();
    $invoice = (new CreateInvoiceForBookingAction)($booking);

    $payment = (new CreateBookingDownPaymentAction)(
        $invoice->fresh(),
        bookingDataRequest([
            'down_payment_amount' => '500.00',
            'payment_method' => 'cash',
            'payment_reference' => 'REF-001',
        ]),
    );

    $invoice->refresh();

    expect($payment)->toBeInstanceOf(Payment::class)
        ->and($payment->type)->toBe(Payment::TYPE_DOWN_PAYMENT)
        ->and($payment->amount)->toBe('500.00')
        ->and($payment->method)->toBe('cash')
        ->and($invoice->amount_paid)->toBe('500.00')
        ->and($invoice->amount_due)->toBe('750.50')
        ->and($invoice->status)->toBe(Invoice::STATUS_PARTIAL);
});

test('create receipt for payment action creates a receipt', function () {
    $booking = createTenantBooking();
    $invoice = (new CreateInvoiceForBookingAction)($booking);
    $payment = (new CreateBookingDownPaymentAction)(
        $invoice->fresh(),
        bookingDataRequest([
            'down_payment_amount' => '500.00',
            'payment_method' => 'cash',
        ]),
    );

    $receipt = (new CreateReceiptForPaymentAction)($payment);

    expect($receipt)->toBeInstanceOf(Receipt::class)
        ->and($receipt->payment_id)->toBe($payment->id)
        ->and($receipt->receipt_number)->toBe('RCP-000001');
});

test('create booking action stores nid image in files table', function () {
    $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]));

    $user = User::factory()->create([
        'tenant_id' => $tenant->getKey(),
    ]);

    $booking = app(CreateBookingAction::class)($tenant, $user, bookingDataRequest([
        'nid_image_url' => 'bookings/nid.jpg',
    ]));

    $file = File::query()
        ->where('fileable_id', $booking->id)
        ->where('fileable_type', Booking::class)
        ->where('category', Booking::NID_IMAGE_CATEGORY)
        ->first();

    expect($file)->not->toBeNull()
        ->and($file->path)->toBe('bookings/nid.jpg')
        ->and($file->tenant_id)->toBe('hotel-alpha')
        ->and($booking->getAttributes()['nid_image_url'] ?? null)->toBeNull();

    $response = BookingDataResponse::fromBooking($booking);

    expect($response->nid_image_url)->toContain('bookings/nid.jpg');
});

test('create booking use case creates booking and invoice without down payment', function () {
    $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]));

    $booking = app(CreateBookingUseCase::class)($tenant, bookingDataRequest());

    expect($booking->invoice)->not->toBeNull()
        ->and($booking->invoice->status)->toBe(Invoice::STATUS_ISSUED)
        ->and($booking->invoice->amount_paid)->toBe('0.00')
        ->and(Payment::query()->exists())->toBeFalse()
        ->and(Receipt::query()->exists())->toBeFalse();

    $guest = User::query()->where('email', 'guest@example.com')->firstOrFail();

    expect($guest->tenant_id)->toBe('hotel-alpha')
        ->and($guest->hasRole('customer'))->toBeTrue();
});

test('create booking use case uses provided user id and assigns customer role', function () {
    $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]));

    $customer = User::factory()->create([
        'tenant_id' => $tenant->getKey(),
        'name' => 'Existing Customer',
        'email' => 'customer@example.com',
        'phone_number' => '01711111111',
    ]);

    $booking = app(CreateBookingUseCase::class)($tenant, bookingDataRequest([
        'user_id' => $customer->id,
    ]));

    expect($booking->user_id)->toBe($customer->id)
        ->and($customer->fresh()->hasRole('customer'))->toBeTrue();
});

test('create booking use case creates invoice payment and receipt for down payment', function () {
    $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]));

    $booking = app(CreateBookingUseCase::class)($tenant, bookingDataRequest([
        'down_payment_amount' => '500.00',
        'payment_method' => 'cash',
        'payment_reference' => 'REF-001',
    ]));

    expect($booking->invoice)->not->toBeNull()
        ->and($booking->invoice->status)->toBe(Invoice::STATUS_PARTIAL)
        ->and($booking->invoice->amount_paid)->toBe('500.00')
        ->and($booking->invoice->payments)->toHaveCount(1)
        ->and($booking->invoice->payments->first()->receipt)->not->toBeNull()
        ->and($booking->invoice->payments->first()->receipt->receipt_number)->toBe('RCP-000001');
});

test('booking response includes invoice payments and receipts', function () {
    $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]));

    $booking = app(CreateBookingUseCase::class)($tenant, bookingDataRequest([
        'down_payment_amount' => '500.00',
        'payment_method' => 'cash',
        'payment_reference' => 'REF-001',
    ]));

    $response = BookingDataResponse::fromBooking($booking);

    expect($response->invoice)->not->toBeNull()
        ->and($response->invoice->invoice_number)->toBe('INV-000001')
        ->and($response->invoice->payments)->toHaveCount(1)
        ->and($response->invoice->payments[0]->amount)->toBe('500.00')
        ->and($response->invoice->payments[0]->method)->toBe('cash')
        ->and($response->invoice->payments[0]->reference)->toBe('REF-001')
        ->and($response->invoice->payments[0]->receipt)->not->toBeNull()
        ->and($response->invoice->payments[0]->receipt->receipt_number)->toBe('RCP-000001');
});

test('store booking request validates booking payload shape', function () {
    $validator = Validator::make([
        'guest_name' => 'Guest One',
        'guest_email' => 'not-an-email',
        'assigned_room_number' => '301',
        'check_in' => '2026-06-03',
        'check_out' => '2026-06-01',
        'nid_number' => '1234567890',
        'discount' => '10',
    ], BookingDataRequest::rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->keys())->toContain(
            'guest_email',
            'room_id',
            'room_quantity',
            'check_out',
        );
});
