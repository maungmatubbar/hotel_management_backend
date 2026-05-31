<?php

namespace App\Http\Requests;

use App\Domain\Booking\DTO\BookingDataRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'payment_option' => ['required', 'string', Rule::in(['pay_now', 'pay_later'])],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests' => ['required', 'integer', 'min:1'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'room_quantity' => ['required', 'integer', 'min:1'],
            'stay_nights' => ['required', 'integer', 'min:1'],
        ];
    }

    public function isPayNow(): bool
    {
        return $this->validated('payment_option') === 'pay_now';
    }

    public function toBookingDataRequest(): BookingDataRequest
    {
        $validated = $this->validated();

        return BookingDataRequest::from([
            'guest_name' => $validated['customer_name'],
            'guest_phone' => $validated['phone_number'] ?? null,
            'guest_email' => $validated['email'],
            'guest_address' => $validated['address'] ?? null,
            'room_id' => (int) $validated['room_id'],
            'assigned_room_number' => 'To be assigned',
            'nid_number' => null,
            'nid_image_url' => null,
            'room_quantity' => (int) $validated['room_quantity'],
            'discount' => '0',
            'promo_code' => null,
            'check_in' => $validated['check_in'],
            'check_out' => $validated['check_out'],
        ]);
    }
}
