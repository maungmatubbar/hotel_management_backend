<?php

namespace App\Domain\Auth\DTO;

use Spatie\LaravelData\Data;
use Spatie\Permission\Models\Permission;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
#[TypeScript]
class PermissionDataResponse extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $guard_name,
    ) {}

    public static function fromPermission(Permission $permission): self
    {
        return new self(
            id: $permission->id,
            name: $permission->name,
            guard_name: $permission->guard_name,
        );
    }
}
