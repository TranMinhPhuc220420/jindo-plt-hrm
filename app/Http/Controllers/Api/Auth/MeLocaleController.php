<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateLocaleRequest;
use App\Services\Auth\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class MeLocaleController extends Controller
{
    public function __invoke(UpdateLocaleRequest $request, AuthService $auth): JsonResponse
    {
        $locale = $request->input('locale');

        return ApiResponse::success(
            $auth->updateLocale($request->user(), is_string($locale) ? $locale : null),
        );
    }
}
