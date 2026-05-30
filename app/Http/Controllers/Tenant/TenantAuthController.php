<?php

namespace App\Http\Controllers\Tenant;

use App\Application\Tenant\LoginTenantUserUseCase;
use App\Domain\Tenant\DTO\TenantUserDataResponse;
use App\Domain\Tenant\DTO\TenantUserLoginDataRequest;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantAuthController extends Controller
{
    public function login(Tenant $tenant, LoginTenantUserUseCase $loginTenantUserUseCase): JsonResponse
    {
        return $this->successResponse(
            data: $loginTenantUserUseCase($tenant, TenantUserLoginDataRequest::from(request())),
        );
    }

    public function me(Request $request, Tenant $tenant): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user instanceof User && (string) $user->tenant_id === (string) $tenant->getKey(),
            Response::HTTP_FORBIDDEN,
        );

        return $this->successResponse(
            data: TenantUserDataResponse::fromUser($user),
        );
    }
}
