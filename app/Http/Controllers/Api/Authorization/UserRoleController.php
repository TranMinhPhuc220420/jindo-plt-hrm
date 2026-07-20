<?php

namespace App\Http\Controllers\Api\Authorization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Authorization\SyncUserRolesRequest;
use App\Http\Resources\RoleResource;
use App\Models\User;
use App\Services\Authorization\RoleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class UserRoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roles,
    ) {}

    public function index(User $user): JsonResponse
    {
        $this->authorize('viewRoles', $user);

        $roles = $this->roles->userRoles($user);

        return ApiResponse::success(
            RoleResource::collection($roles)->resolve(),
        );
    }

    public function sync(SyncUserRolesRequest $request, User $user): JsonResponse
    {
        $this->authorize('assignRoles', $user);

        $user = $this->roles->syncUserRoles(
            $user,
            $request->validated('roles'),
        );

        return ApiResponse::success(
            RoleResource::collection($user->roles)->resolve(),
            'User roles updated.',
        );
    }
}
