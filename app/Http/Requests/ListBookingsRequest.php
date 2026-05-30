<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListBookingsRequest extends FormRequest
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
            'filter' => ['sometimes', 'array'],
            'filter.booking_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter.status' => ['sometimes', 'nullable', 'string', Rule::enum(BookingStatus::class)],
            'filter.customer_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter.customer_email' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter.customer_phone_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
