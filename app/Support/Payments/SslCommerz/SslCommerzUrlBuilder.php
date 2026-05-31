<?php

namespace App\Support\Payments\SslCommerz;

use App\Models\Booking;

class SslCommerzUrlBuilder
{
    public static function fromTemplate(string $template, Booking $booking): string
    {
        $booking->loadMissing('invoice');

        $replacements = [
            '{tenant}' => (string) $booking->tenant_id,
            '{invoice_number}' => (string) $booking->invoice?->invoice_number,
            '{invoice_id}' => (string) $booking->invoice?->id,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
