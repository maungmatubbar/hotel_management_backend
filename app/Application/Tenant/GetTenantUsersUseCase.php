<?php

namespace App\Application\Tenant;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class GetTenantUsersUseCase
{
    /**
     * @return Collection<int, User>
     */
    public function __invoke(Tenant $tenant): Collection
    {
        return User::query()
            ->whereBelongsTo($tenant)
            ->with('roles.permissions', 'permissions')
            ->latest('id')
            ->get();
    }
}
