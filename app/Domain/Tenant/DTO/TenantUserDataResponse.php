<?php

namespace App\Domain\Tenant\DTO;

use App\Domain\Auth\DTO\RoleDataResponse;
use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\Permission\Models\Role;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
#[TypeScript]
class TenantUserDataResponse extends Data
{
    /**
     * @param  array<int, RoleDataResponse>  $roles
     */
    public function __construct(
        public readonly int $id,
        public readonly string $tenant_id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone_number,
        public readonly array $roles,
    ) {}

    public static function fromUser(User $user): self
    {
        $user->loadMissing('roles.permissions');

        return new self(
            id: $user->id,
            tenant_id: (string) $user->tenant_id,
            name: $user->name,
            email: $user->email,
            phone_number: $user->phone_number,
            roles: $user->roles
                ->map(fn (Role $role): RoleDataResponse => RoleDataResponse::fromRole($role))
                ->values()
                ->all(),
        );
    }
}
