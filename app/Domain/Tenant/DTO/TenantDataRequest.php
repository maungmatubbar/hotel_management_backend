<?php

namespace App\Domain\Tenant\DTO;

use Spatie\LaravelData\Data;

class TenantDataRequest extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $id = null,
        public readonly ?string $domain = null,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'id' => ['nullable', 'string', 'alpha_dash', 'max:255', 'unique:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:domains,domain'],
        ];
    }
}
