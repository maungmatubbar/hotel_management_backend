<?php

namespace App\Domain\Auth\DTO;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
#[TypeScript]
class AuthDataResponse extends Data
{
    public function __construct(
        public readonly string $token,
        public readonly string $token_type,
        public readonly UserDataResponse $user,
    ) {}

    public static function fromUser(User $user, string $token): self
    {
        return new self(
            token: $token,
            token_type: 'Bearer',
            user: UserDataResponse::fromUser($user),
        );
    }
}
