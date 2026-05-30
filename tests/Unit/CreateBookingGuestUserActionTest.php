<?php

use App\Domain\Booking\Action\CreateBookingGuestUserAction;
use App\Domain\Booking\DTO\BookingDataRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('it creates a guest user for a booking', function () {
    $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->create([
        'id' => 'hotel-alpha',
        'name' => 'Hotel Alpha',
    ]));

    $data = new BookingDataRequest(
        guest_name: 'Guest One',
        guest_phone: '01700000000',
        guest_email: 'guest@example.com',
        guest_address: 'House 12, Road 3',
        room_id: 1,
        assigned_room_number: '301',
        nid_number: '1234567890',
        nid_image_url: null,
        room_quantity: 1,
        discount: '0',
        promo_code: null,
        check_in: '2026-06-01',
        check_out: '2026-06-03',
    );

    $user = (new CreateBookingGuestUserAction)($tenant, $data);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Guest One')
        ->and($user->email)->toBe('guest@example.com')
        ->and($user->phone_number)->toBe('01700000000')
        ->and($user->tenant_id)->toBe('hotel-alpha')
        ->and($user->password)->not->toBeEmpty();
});
