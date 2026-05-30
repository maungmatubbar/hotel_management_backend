<?php

namespace App\Domain\Auth\DTO;

use Spatie\LaravelData\Data;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
#[TypeScript]
class RoleDataResponse extends Data
{
    /**
     * @param  array<int, PermissionDataResponse>  $permissions
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $guard_name,
        public readonly array $permissions,
    ) {}

    public static function fromRole(Role $role): self
    {
        $role->loadMissing('permissions');

        return new self(
            id: $role->id,
            name: $role->name,
            guard_name: $role->guard_name,
            permissions: $role->permissions
                ->map(fn (Permission $permission): PermissionDataResponse => PermissionDataResponse::fromPermission($permission))
                ->values()
                ->all(),
        );
    }
}
