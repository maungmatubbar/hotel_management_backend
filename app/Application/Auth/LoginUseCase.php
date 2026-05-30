<?php

namespace App\Application\Auth;

use App\Domain\Auth\DTO\AuthDataRequest;
use App\Domain\Auth\DTO\AuthDataResponse;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginUseCase
{
    public const SUPER_ADMIN_ROLE = 'super admin';

    public function __invoke(AuthDataRequest $data): AuthDataResponse
    {
        $user = User::query()
            ->where('email', $data->identifier)
            ->first();

        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => __('auth.failed'),
            ]);
        }

        if (! $user->hasRole(self::SUPER_ADMIN_ROLE)) {
            throw ValidationException::withMessages([
                'identifier' => __('auth.failed'),
            ]);
        }

        $user->loadMissing('roles.permissions', 'permissions');

        return AuthDataResponse::fromUser(
            user: $user,
            token: $user->createToken($data->tokenName())->plainTextToken,
        );
    }
}
