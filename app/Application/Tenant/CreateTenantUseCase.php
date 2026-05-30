<?php

namespace App\Application\Tenant;

use App\Domain\Tenant\DTO\TenantDataRequest;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class CreateTenantUseCase
{
    public function __invoke(TenantDataRequest $data): Tenant
    {
        return DB::transaction(function () use ($data): Tenant {
            $attributes = [
                'name' => $data->name,
            ];

            if ($data->id !== null) {
                $attributes['id'] = $data->id;
            }

            $tenant = Tenant::query()->create($attributes);

            if (! empty($data->domain)) {
                $tenant->createDomain($data->domain);
            }

            return $tenant->refresh()->load('domains');
        });
    }
}
