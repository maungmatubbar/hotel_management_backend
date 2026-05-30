<?php

namespace App\Domain\Tenant\DTO;

use App\Models\Tenant;
use Spatie\LaravelData\Data;
use Stancl\Tenancy\Database\Models\Domain;

class TenantDataResponse extends Data
{
    /**
     * @param  array<int, string>  $domains
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $name,
        public readonly ?string $domain,
        public readonly array $domains,
    ) {}

    public static function fromTenant(Tenant $tenant): self
    {
        $tenant->loadMissing('domains');

        return new self(
            id: (string) $tenant->getKey(),
            name: $tenant->getAttribute('name'),
            domain: $tenant->domains->first()?->domain,
            domains: $tenant->domains
                ->map(fn (Domain $domain): string => $domain->domain)
                ->values()
                ->all(),
        );
    }
}
