<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreSslCommerzInvoicePaymentRequest extends FormRequest
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
            'tran_id' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'status' => ['required', 'string', 'max:50'],
            'card_type' => ['nullable', 'string', 'max:50'],
            'tran_date' => ['nullable', 'date'],
            'val_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tran_id.required' => 'SSLCommerz must redirect here with tran_id. Do not open this URL manually.',
            'amount.required' => 'SSLCommerz must redirect here with amount. Do not open this URL manually.',
            'status.required' => 'SSLCommerz must redirect here with status. Do not open this URL manually.',
        ];
    }

    public function isSuccessful(): bool
    {
        return in_array(Str::upper((string) $this->validated('status')), ['VALID', 'VALIDATED'], true);
    }

    public function amount(): string
    {
        return (string) $this->validated('amount');
    }

    public function method(): string
    {
        return (string) ($this->validated('card_type') ?: 'sslcommerz');
    }

    public function reference(): string
    {
        return (string) $this->validated('tran_id');
    }

    public function paidAt(): ?string
    {
        $paidAt = $this->validated('tran_date');

        return is_string($paidAt) ? $paidAt : null;
    }
}
