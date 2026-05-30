<?php

use App\Domain\Booking\DTO\BookingDataRequest;
use App\Enums\BookingStatus;
use App\Http\Requests\StoreBookingRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

test('store booking request uses booking dto validation rules', function () {
    $request = new StoreBookingRequest;

    expect($request->authorize())->toBeTrue()
        ->and($request->rules())->toEqual(BookingDataRequest::rules());
});

test('store booking request validates booking payload shape', function () {
    $validator = Validator::make([
        'guest_email' => 'not-an-email',
        'check_in' => '2026-06-03',
        'check_out' => '2026-06-01',
        'status' => 'unknown',
    ], (new StoreBookingRequest)->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->keys())->toContain(
            'guest_name',
            'guest_email',
            'room_id',
            'assigned_room_number',
            'room_quantity',
            'check_out',
            'status',
        );
});

test('store booking request accepts known booking status values', function () {
    $validator = Validator::make([
        'guest_name' => 'Guest One',
        'guest_email' => 'guest@example.com',
        'assigned_room_number' => '301',
        'room_quantity' => 1,
        'discount' => '0',
        'check_in' => '2026-06-01',
        'check_out' => '2026-06-03',
        'status' => BookingStatus::Confirmed->value,
    ], (new StoreBookingRequest)->rules());

    expect($validator->errors()->has('status'))->toBeFalse();
});
