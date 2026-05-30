<?php

namespace App\Domain\Booking\Action;

use App\Domain\Booking\DTO\BookingDataRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateBookingGuestUserAction
{
    public function __invoke(Tenant $tenant, BookingDataRequest $data): User
    {
        return User::query()->create([
            'name' => $data->guest_name,
            'email' => $data->guest_email,
            'phone_number' => $data->guest_phone,
            'tenant_id' => $tenant->getKey(),
            'password' => Hash::make('password'),
        ]);
    }
}
