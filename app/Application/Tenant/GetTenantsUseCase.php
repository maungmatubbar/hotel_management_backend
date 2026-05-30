<?php

namespace App\Application\Tenant;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;

class GetTenantsUseCase
{
    /**
     * @return Collection<int, Tenant>
     */
    public function __invoke(): Collection
    {
        return Tenant::query()
            ->with('domains')
            ->latest('id')
            ->get();
    }
}
