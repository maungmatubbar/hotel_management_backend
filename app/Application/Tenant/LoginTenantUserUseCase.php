<?php

namespace App\Application\Tenant;

use App\Domain\Tenant\DTO\TenantUserAuthDataResponse;
use App\Domain\Tenant\DTO\TenantUserLoginDataRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginTenantUserUseCase
{
    public function __invoke(Tenant $tenant, TenantUserLoginDataRequest $data): TenantUserAuthDataResponse
    {
        $user = User::query()
            ->whereBelongsTo($tenant)
            ->where('email', $data->identifier)
            ->first();

        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => __('auth.failed'),
            ]);
        }

        $user->loadMissing('roles.permissions', 'permissions');

        return TenantUserAuthDataResponse::fromUser(
            user: $user,
            token: $user->createToken($data->tokenName())->plainTextToken,
        );
    }
}
