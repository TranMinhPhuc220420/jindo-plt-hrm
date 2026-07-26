<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Services\Auth\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $this->auth->login(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            remember: $request->boolean('remember'),
        );

        return ApiResponse::success($data);
    }

    public function challengeTwoFactor(TwoFactorChallengeRequest $request): JsonResponse
    {
        $recovery = filled($request->input('recovery_code'));
        $code = $recovery
            ? $request->string('recovery_code')->toString()
            : $request->string('code')->toString();

        $data = $this->auth->challengeTwoFactor(
            code: $code,
            recovery: $recovery,
        );

        return ApiResponse::success($data);
    }

    public function logout(): JsonResponse
    {
        $this->auth->logout();

        return ApiResponse::success(null, 'Logged out.');
    }
}
