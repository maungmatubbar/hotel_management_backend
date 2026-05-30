<?php

namespace App\Domain\Tenant\Action;

use App\Domain\Tenant\DTO\TenantUserDataRequest;
use App\Models\Tenant;
use App\Models\User;

class CreateTenantUserAction
{
    public function __invoke(Tenant $tenant, TenantUserDataRequest $data): User
    {
        return User::query()->create([
            'name' => $data->name,
            'email' => $data->email,
            'phone_number' => $data->phone_number,
            'tenant_id' => $tenant->getKey(),
            'password' => $data->password,
        ]);
    }
}
