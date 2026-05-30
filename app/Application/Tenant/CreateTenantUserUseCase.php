<?php

namespace App\Application\Tenant;

use App\Domain\Tenant\Action\CreateTenantUserAction;
use App\Domain\Tenant\DTO\TenantUserDataRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class CreateTenantUserUseCase
{
    public const GUARD_NAME = 'sanctum';

    public function __construct(
        private readonly CreateTenantUserAction $createTenantUserAction,
    ) {}

    public function __invoke(Tenant $tenant, TenantUserDataRequest $data): User
    {
        return DB::transaction(function () use ($tenant, $data): User {
            $user = ($this->createTenantUserAction)($tenant, $data);

            $user->assignRole(Role::findOrCreate($data->role, self::GUARD_NAME));

            return $user->load('roles.permissions', 'permissions');
        });
    }
}
