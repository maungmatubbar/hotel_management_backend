<?php

namespace App\Application\Booking;

use App\Domain\Billing\Action\CreateBookingDownPaymentAction;
use App\Domain\Billing\Action\CreateInvoiceForBookingAction;
use App\Domain\Billing\Action\CreateReceiptForPaymentAction;
use App\Domain\Booking\Action\AssignBookingCustomerRoleAction;
use App\Domain\Booking\Action\CreateBookingAction;
use App\Domain\Booking\Action\CreateBookingGuestUserAction;
use App\Domain\Booking\DTO\BookingDataRequest;
use App\Models\Booking;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateBookingUseCase
{
    public function __invoke(Tenant $tenant, BookingDataRequest $data): Booking
    {
        return DB::transaction(function () use ($tenant, $data): Booking {
            $user = $this->resolveGuestUser($tenant, $data);
            app(AssignBookingCustomerRoleAction::class)($user);

            $booking = app(CreateBookingAction::class)($tenant, $user, $data);
            $invoice = app(CreateInvoiceForBookingAction::class)($booking);

            if ($data->hasDownPayment()) {
                $payment = app(CreateBookingDownPaymentAction::class)(
                    invoice: $invoice,
                    amount: (string) $data->down_payment_amount,
                    method: (string) $data->payment_method,
                    reference: $data->payment_reference,
                    paidAt: $data->paid_at,
                );
                app(CreateReceiptForPaymentAction::class)($payment);
            }

            return $booking->load([
                'user',
                'invoice.payments.receipt',
                'files' => fn ($query) => $query->where('category', Booking::NID_IMAGE_CATEGORY),
            ]);
        });
    }

    private function resolveGuestUser(Tenant $tenant, BookingDataRequest $data): User
    {
        if ($data->user_id !== null) {
            return $this->resolveTenantUser($tenant, User::query()->findOrFail($data->user_id));
        }

        $user = User::query()
            ->when(
                $data->guest_email !== null,
                fn ($query) => $query->where('email', $data->guest_email),
            )
            ->when(
                $data->guest_phone !== null,
                fn ($query) => $query->orWhere('phone_number', $data->guest_phone),
            )
            ->first();

        if ($user instanceof User) {
            $this->resolveTenantUser($tenant, $user);

            $user->update([
                'name' => $data->guest_name,
                'phone_number' => $data->guest_phone,
            ]);

            return $user;
        }

        return app(CreateBookingGuestUserAction::class)($tenant, $data);
    }

    private function resolveTenantUser(Tenant $tenant, User $user): User
    {
        if ((string) $user->tenant_id !== (string) $tenant->getKey()) {
            throw ValidationException::withMessages([
                'user_id' => __('validation.exists', ['attribute' => 'user']),
            ]);
        }

        return $user;
    }
}
