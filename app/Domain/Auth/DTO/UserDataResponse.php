<?php

namespace App\Domain\Auth\DTO;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
#[TypeScript]
class UserDataResponse extends Data
{
    /**
     * @param  array<int, RoleDataResponse>  $roles
     * @param  array<int, PermissionDataResponse>  $permissions
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $email_verified_at,
        public readonly ?string $phone_number,
        public readonly array $roles,
        public readonly array $permissions,
    ) {}

    public static function fromUser(User $user): self
    {
        $user->loadMissing('roles.permissions', 'permissions');

        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            email_verified_at: $user->email_verified_at?->toISOString(),
            phone_number: $user->phone_number,
            roles: $user->roles
                ->map(fn (Role $role): RoleDataResponse => RoleDataResponse::fromRole($role))
                ->values()
                ->all(),
            permissions: $user->getAllPermissions()
                ->map(fn (Permission $permission): PermissionDataResponse => PermissionDataResponse::fromPermission($permission))
                ->values()
                ->all(),
        );
    }
}
