<?php

namespace App\Http\Middleware;

use App\Application\Auth\LoginUseCase;
use App\Models\Tenant;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenantByRouteParameter
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);
        $user = $request->user();

        abort_unless($user instanceof User, Response::HTTP_UNAUTHORIZED);

        abort_unless(
            (string) $user->tenant_id === (string) $tenant->getKey()
                || $user->hasRole(LoginUseCase::SUPER_ADMIN_ROLE),
            Response::HTTP_FORBIDDEN,
        );

        tenancy()->initialize($tenant);

        try {
            return $next($request);
        } finally {
            tenancy()->end();
        }
    }

    private function resolveTenant(Request $request): Tenant
    {
        $tenant = $request->route('tenant');

        if ($tenant instanceof Tenant) {
            return $tenant;
        }

        return Tenant::query()->findOrFail((string) $tenant);
    }
}
