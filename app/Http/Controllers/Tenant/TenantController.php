<?php

namespace App\Http\Controllers\Tenant;

use App\Application\Auth\LoginUseCase;
use App\Application\Tenant\CreateTenantUseCase;
use App\Application\Tenant\CreateTenantUserUseCase;
use App\Application\Tenant\GetTenantUsersUseCase;
use App\Application\Tenant\GetTenantsUseCase;
use App\Domain\Tenant\DTO\TenantDataRequest;
use App\Domain\Tenant\DTO\TenantDataResponse;
use App\Domain\Tenant\DTO\TenantUserDataRequest;
use App\Domain\Tenant\DTO\TenantUserDataResponse;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantController extends Controller
{
    public function index(Request $request, GetTenantsUseCase $getTenantsUseCase): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        return $this->successResponse(
            data: $getTenantsUseCase()
                ->map(fn (Tenant $tenant): TenantDataResponse => TenantDataResponse::fromTenant($tenant))
                ->values()
                ->all(),
        );
    }

    public function store(Request $request, CreateTenantUseCase $createTenantUseCase): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        return $this->successResponse(
            data: TenantDataResponse::fromTenant($createTenantUseCase(TenantDataRequest::from($request))),
            status: Response::HTTP_CREATED,
        );
    }

    public function storeUser(Request $request, Tenant $tenant, CreateTenantUserUseCase $createTenantUserUseCase): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        return $this->successResponse(
            data: TenantUserDataResponse::fromUser($createTenantUserUseCase($tenant, TenantUserDataRequest::from($request))),
            status: Response::HTTP_CREATED,
        );
    }

    public function users(Request $request, Tenant $tenant, GetTenantUsersUseCase $getTenantUsersUseCase): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        return $this->successResponse(
            data: $getTenantUsersUseCase($tenant)
                ->map(fn (User $user): TenantUserDataResponse => TenantUserDataResponse::fromUser($user))
                ->values()
                ->all(),
        );
    }

    private function ensureSuperAdmin(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user instanceof User && $user->hasRole(LoginUseCase::SUPER_ADMIN_ROLE),
            Response::HTTP_FORBIDDEN,
        );
    }
}
