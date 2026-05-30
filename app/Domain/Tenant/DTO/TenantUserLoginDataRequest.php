<?php

namespace App\Domain\Tenant\DTO;

use Spatie\LaravelData\Data;

class TenantUserLoginDataRequest extends Data
{
    public function __construct(
        public readonly string $identifier,
        public readonly string $password,
        public readonly ?string $device_name = null,
    ) {}

    public function tokenName(): string
    {
        return $this->device_name ?: 'tenant-user';
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'identifier' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
