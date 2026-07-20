<?php

namespace App\Http\Controllers\Api\Authorization;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Models\Role;
use App\Services\Authorization\RoleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    public function __construct(
        private readonly RoleService $roles,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $permissions = $this->roles->listPermissions();

        return ApiResponse::success(
            PermissionResource::collection($permissions)->resolve(),
        );
    }
}
