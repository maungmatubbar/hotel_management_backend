<?php

namespace App\Http\Controllers\Auth;

use App\Application\Auth\LoginUseCase;
use App\Domain\Auth\DTO\AuthDataRequest;
use App\Domain\Auth\DTO\UserDataResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function login(AuthDataRequest $request, LoginUseCase $loginUseCase): JsonResponse
    {
        return $this->successResponse(
            data: $loginUseCase($request),
        );
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user instanceof User && $user->hasRole(LoginUseCase::SUPER_ADMIN_ROLE),
            Response::HTTP_FORBIDDEN,
        );

        return $this->successResponse(
            data: UserDataResponse::fromUser($user),
        );
    }
}
