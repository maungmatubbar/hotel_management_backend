<?php

namespace App\Domain\Tenant\DTO;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class TenantUserDataRequest extends Data
{
    public const TENANT_ADMIN_ROLE = 'admin';

    public const TENANT_STAFF_ROLE = 'staff';

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly string $role,
        public readonly ?string $phone_number = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in([
                self::TENANT_ADMIN_ROLE,
                self::TENANT_STAFF_ROLE,
            ])],
        ];
    }
}
