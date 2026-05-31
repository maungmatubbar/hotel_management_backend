<?php

use App\Domain\Booking\Action\UpdateBookingNidImageAction;
use App\Http\Requests\UpdateBookingNidImageRequest;
use App\Models\Booking;
use App\Models\File as FileRecord;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

use function Pest\Laravel\patchJson;
use function Pest\Laravel\withoutMiddleware;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    updateBookingNidImageTestMigrateSchema();
});

function updateBookingNidImageTestMigrateSchema(): void
{
    Schema::dropIfExists('files');
    Schema::dropIfExists('bookings');
    Schema::dropIfExists('rooms');

    (require database_path('migrations/2026_05_28_173516_create_rooms_table.php'))->up();
    (require database_path('migrations/2026_05_28_180845_create_files_table.php'))->up();
    (require database_path('migrations/2026_05_29_074436_add_deleted_at_to_files_table.php'))->up();
    (require database_path('migrations/2026_05_29_134947_create_bookings_table.php'))->up();
    (require database_path('migrations/2026_05_30_100654_add_booking_number_and_status_to_bookings_table.php'))->up();
}

function updateBookingNidImageTestCreateRoom(array $overrides = []): Room
{
    return Room::factory()->create([
        'tenant_id' => $overrides['tenant_id'] ?? 'hotel-alpha',
        'room_name' => $overrides['room_name'] ?? 'Business Twin Room',
    ]);
}

function updateBookingNidImageTestCreateBooking(array $overrides = []): Booking
{
    $room = $overrides['room_record'] ?? updateBookingNidImageTestCreateRoom();

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

test('update booking nid image endpoint route is registered', function (): void {
    expect(Route::has('tenants.bookings.nid-image.update'))->toBeTrue();
});

test('update booking nid image request validates image path payload', function (): void {
    $request = new UpdateBookingNidImageRequest;

    $validator = Validator::make([
        'nid_image_url' => str_repeat('A', 2049),
    ], $request->rules());

    expect($request->authorize())->toBeTrue()
        ->and($validator->fails())->toBeTrue()
        ->and($validator->errors()->keys())->toContain('nid_image_url');
});

test('update booking nid image action replaces existing nid image', function (): void {
    $booking = updateBookingNidImageTestCreateBooking();

    $existingFile = $booking->files()->create([
        'category' => Booking::NID_IMAGE_CATEGORY,
        'tenant_id' => 'hotel-alpha',
        'disk' => 'public',
        'path' => 'bookings/old-nid.jpg',
    ]);

    $updatedBooking = app(UpdateBookingNidImageAction::class)(
        $booking,
        'hotel-alpha',
        '/storage/bookings/new-nid.jpg',
    );

    $activeFiles = FileRecord::query()
        ->where('fileable_id', $booking->id)
        ->where('fileable_type', Booking::class)
        ->where('category', Booking::NID_IMAGE_CATEGORY)
        ->get();

    expect($activeFiles)->toHaveCount(1)
        ->and($activeFiles->first()?->path)->toBe('bookings/new-nid.jpg')
        ->and($activeFiles->first()?->tenant_id)->toBe('hotel-alpha')
        ->and(FileRecord::withTrashed()->whereKey($existingFile->id)->first()?->deleted_at)->not->toBeNull()
        ->and($updatedBooking->files)->toHaveCount(1)
        ->and($updatedBooking->files->first()?->path)->toBe('bookings/new-nid.jpg');
});

test('update booking nid image endpoint updates booking response', function (): void {
    withoutMiddleware();

    $booking = updateBookingNidImageTestCreateBooking();

    $response = patchJson("/api/tenants/hotel-alpha/bookings/{$booking->id}/nid-image", [
        'nid_image_url' => 'bookings/nid-replacement.jpg',
    ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $booking->id);

    $nidImageFileExists = FileRecord::query()
        ->where('fileable_id', $booking->id)
        ->where('fileable_type', Booking::class)
        ->where('category', Booking::NID_IMAGE_CATEGORY)
        ->where('path', 'bookings/nid-replacement.jpg')
        ->exists();

    expect($response->json('data.nid_image_url'))->toContain('bookings/nid-replacement.jpg')
        ->and($nidImageFileExists)->toBeTrue();
});
