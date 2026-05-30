<?php

namespace App\Domain\Tenant\DTO;

use App\Models\User;
use Spatie\LaravelData\Data;

class TenantUserAuthDataResponse extends Data
{
    public function __construct(
        public readonly string $token,
        public readonly string $token_type,
        public readonly TenantUserDataResponse $user,
    ) {}

    public static function fromUser(User $user, string $token): self
    {
        return new self(
            token: $token,
            token_type: 'Bearer',
            user: TenantUserDataResponse::fromUser($user),
        );
    }
}
