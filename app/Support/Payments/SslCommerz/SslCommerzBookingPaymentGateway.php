<?php

namespace App\Support\Payments\SslCommerz;

use App\Domain\Billing\Contracts\BookingPaymentGateway;
use App\Domain\Billing\DTO\BookingPaymentRedirectData;
use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SslCommerzBookingPaymentGateway implements BookingPaymentGateway
{
    public function redirectForBooking(Booking $booking): BookingPaymentRedirectData
    {
        $booking->loadMissing(['invoice', 'user']);

        if ($booking->invoice === null) {
            throw new RuntimeException('Booking invoice is required before starting payment.');
        }

        $transactionId = $this->transactionId($booking);

        $response = Http::asForm()
            ->timeout(15)
            ->post((string) config('services.sslcommerz.endpoint'), [
                'store_id' => config('services.sslcommerz.store_id'),
                'store_passwd' => config('services.sslcommerz.store_password'),
                'total_amount' => $booking->invoice->amount_due,
                'currency' => config('services.sslcommerz.currency'),
                'tran_id' => $transactionId,
                'success_url' => route('tenants.public.invoices.sslcommerz.success.by-number', [
                    'tenant' => $booking->tenant_id,
                    'invoiceNumber' => $booking->invoice->invoice_number,
                ]),
                'fail_url' => route('tenants.public.invoices.sslcommerz.fail.by-number', [
                    'tenant' => $booking->tenant_id,
                    'invoiceNumber' => $booking->invoice->invoice_number,
                ]),
                'cancel_url' => route('tenants.public.invoices.sslcommerz.cancel.by-number', [
                    'tenant' => $booking->tenant_id,
                    'invoiceNumber' => $booking->invoice->invoice_number,
                ]),
                'ipn_url' => route('tenants.public.invoices.sslcommerz.ipn.by-number', [
                    'tenant' => $booking->tenant_id,
                    'invoiceNumber' => $booking->invoice->invoice_number,
                ]),
                'cus_name' => $booking->guest_name,
                'cus_email' => $booking->guest_email,
                'cus_add1' => $booking->guest_address ?? 'Not provided',
                'cus_city' => config('services.sslcommerz.customer_city'),
                'cus_country' => config('services.sslcommerz.customer_country'),
                'cus_phone' => $booking->guest_phone ?? 'Not provided',
                'shipping_method' => 'NO',
                'product_name' => $booking->room,
                'product_category' => 'Hotel Booking',
                'product_profile' => 'general',
                'value_a' => $booking->tenant_id,
                'value_b' => $booking->id,
                'value_c' => $booking->booking_number,
            ])
            ->throw()
            ->json();

        $paymentUrl = is_array($response) ? ($response['GatewayPageURL'] ?? null) : null;

        if (! is_string($paymentUrl) || $paymentUrl === '') {
            throw new RuntimeException('SSLCommerz did not return a payment URL.');
        }

        return new BookingPaymentRedirectData(
            payment_url: $paymentUrl,
            transaction_id: $transactionId,
        );
    }

    private function transactionId(Booking $booking): string
    {
        return sprintf(
            '%s-%s',
            $booking->booking_number ?? Booking::numberForId($booking->id),
            Str::upper(Str::random(8)),
        );
    }
}
