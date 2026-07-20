<?php

namespace App\Http\Controllers\Api\Authorization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Authorization\StoreRoleRequest;
use App\Http\Requests\Authorization\SyncRolePermissionsRequest;
use App\Http\Requests\Authorization\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Services\Authorization\RoleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roles,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $paginator = $this->roles->listRoles(
            perPage: (int) $request->integer('per_page', 20),
        );

        return ApiResponse::paginated(
            $paginator->through(fn (Role $role) => (new RoleResource($role))->resolve()),
        );
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $role = $this->roles->createRole($request->validated());

        return ApiResponse::created(
            (new RoleResource($role->load('permissions')))->resolve(),
            'Role created.',
        );
    }

    public function show(Role $role): JsonResponse
    {
        $this->authorize('view', $role);

        $role = $this->roles->findRole($role->id);

        return ApiResponse::success(
            (new RoleResource($role))->resolve(),
        );
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        $role = $this->roles->updateRole($role, $request->validated());

        return ApiResponse::success(
            (new RoleResource($role))->resolve(),
            'Role updated.',
        );
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('delete', $role);

        $this->roles->deleteRole($role);

        return ApiResponse::success(null, 'Role deleted.');
    }

    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role): JsonResponse
    {
        $this->authorize('syncPermissions', $role);

        $role = $this->roles->syncPermissions(
            $role,
            $request->validated('permissions'),
        );

        return ApiResponse::success(
            (new RoleResource($role))->resolve(),
            'Role permissions updated.',
        );
    }
}
