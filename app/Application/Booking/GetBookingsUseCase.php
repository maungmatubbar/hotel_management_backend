<?php

namespace App\Application\Booking;

use App\Models\Booking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class GetBookingsUseCase
{
    /**
     * @return LengthAwarePaginator<int, Booking>
     */
    public function __invoke(int $perPage = 15): LengthAwarePaginator
    {
        return QueryBuilder::for(Booking::query())
            ->with([
                'user',
                'invoice.payments.receipt',
                'files' => fn ($query) => $query->where('category', Booking::NID_IMAGE_CATEGORY),
            ])
            ->allowedFilters(
                AllowedFilter::callback('booking_number', function (Builder $query, mixed $value): void {
                    $bookingNumber = $this->filterValue($value);

                    if ($bookingNumber !== '') {
                        $query->whereKey((int) $bookingNumber);
                    }
                }),
                AllowedFilter::callback('customer_name', function (Builder $query, mixed $value): void {
                    $this->whereBookingCustomer($query, $value, 'guest_name', 'name');
                }),
                AllowedFilter::callback('customer_email', function (Builder $query, mixed $value): void {
                    $this->whereBookingCustomer($query, $value, 'guest_email', 'email');
                }),
                AllowedFilter::callback('customer_phone_number', function (Builder $query, mixed $value): void {
                    $this->whereBookingCustomer($query, $value, 'guest_phone', 'phone_number');
                }),
            )
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function whereBookingCustomer(Builder $query, mixed $value, string $bookingColumn, string $userColumn): void
    {
        $search = $this->filterValue($value);

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $query) use ($bookingColumn, $search, $userColumn): void {
            $query
                ->where($bookingColumn, 'like', "%{$search}%")
                ->orWhereHas('user', fn (Builder $query): Builder => $query->where($userColumn, 'like', "%{$search}%"));
        });
    }

    private function filterValue(mixed $value): string
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        return trim((string) $value);
    }
}
