<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'filter' => ['sometimes', 'array'],
            'filter.booking_number' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'filter.customer_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter.customer_email' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter.customer_phone_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
