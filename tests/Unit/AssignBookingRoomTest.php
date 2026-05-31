<?php

use App\Domain\Booking\Action\AssignBookingRoomAction;
use App\Enums\BookingStatus;
use App\Http\Requests\AssignBookingRoomRequest;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    assignBookingRoomTestMigrateSchema();
});

function assignBookingRoomTestMigrateSchema(): void
{
    Schema::dropIfExists('bookings');
    Schema::dropIfExists('rooms');

    (require database_path('migrations/2026_05_28_173516_create_rooms_table.php'))->up();
    (require database_path('migrations/2026_05_29_134947_create_bookings_table.php'))->up();
    (require database_path('migrations/2026_05_30_100654_add_booking_number_and_status_to_bookings_table.php'))->up();
}

function assignBookingRoomTestCreateRoom(array $overrides = []): Room
{
    return Room::factory()->create([
        'tenant_id' => $overrides['tenant_id'] ?? 'hotel-alpha',
        'room_name' => $overrides['room_name'] ?? 'Business Twin Room',
    ]);
}

function assignBookingRoomTestCreateBooking(array $overrides = []): Booking
{
    $room = $overrides['room_record'] ?? assignBookingRoomTestCreateRoom();

    return Booking::query()->create([
        'tenant_id' => $overrides['tenant_id'] ?? 'hotel-alpha',
        'user_id' => $overrides['user_id'] ?? 1,
        'room_id' => $room->id,
        'guest_name' => $overrides['guest_name'] ?? 'Guest One',
        'guest_phone' => $overrides['guest_phone'] ?? '01700000000',
        'guest_email' => $overrides['guest_email'] ?? 'guest@example.com',
        'guest_address' => $overrides['guest_address'] ?? 'House 12, Road 3',
        'room' => $room->room_name,
        'assigned_room_number' => $overrides['assigned_room_number'] ?? '301',
        'nid_number' => $overrides['nid_number'] ?? '1234567890',
        'room_quantity' => $overrides['room_quantity'] ?? 1,
        'discount' => $overrides['discount'] ?? '0',
        'check_in' => $overrides['check_in'] ?? '2026-06-01',
        'check_out' => $overrides['check_out'] ?? '2026-06-03',
    ]);
}

test('assign booking room endpoint route is registered', function (): void {
    expect(Route::has('tenants.bookings.assign-room'))->toBeTrue();
});

test('assign booking room request validates room assignment payload', function (): void {
    $request = new AssignBookingRoomRequest;

    $validator = Validator::make([
        'assigned_room_number' => str_repeat('A', 51),
    ], $request->rules());

    expect($request->authorize())->toBeTrue()
        ->and($validator->fails())->toBeTrue()
        ->and($validator->errors()->keys())->toContain('room_id', 'assigned_room_number');
});

test('assign booking room action updates booking room details', function (): void {
    $booking = assignBookingRoomTestCreateBooking();
    $room = assignBookingRoomTestCreateRoom([
        'room_name' => 'Deluxe King Room',
    ]);

    $updatedBooking = app(AssignBookingRoomAction::class)(
        $booking,
        'hotel-alpha',
        $room->id,
        '502',
    );

    expect($updatedBooking->room_id)->toBe($room->id)
        ->and($updatedBooking->room)->toBe('Deluxe King Room')
        ->and($updatedBooking->assigned_room_number)->toBe('502')
        ->and($updatedBooking->status)->toBe(BookingStatus::CheckIn)
        ->and($booking->fresh()->room_id)->toBe($room->id);
});

test('assign booking room action rejects a room from another tenant', function (): void {
    $booking = assignBookingRoomTestCreateBooking();
    $otherTenantRoom = assignBookingRoomTestCreateRoom([
        'tenant_id' => 'hotel-beta',
        'room_name' => 'Other Tenant Room',
    ]);

    expect(fn () => app(AssignBookingRoomAction::class)(
        $booking,
        'hotel-alpha',
        $otherTenantRoom->id,
        '701',
    ))->toThrow(ValidationException::class)
        ->and($booking->fresh()->room)->toBe('Business Twin Room')
        ->and($booking->fresh()->assigned_room_number)->toBe('301');
});
