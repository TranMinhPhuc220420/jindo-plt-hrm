<?php

namespace App\Http\Controllers\Api\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreDepartmentRequest;
use App\Http\Requests\Organization\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Services\Organization\OrganizationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organization,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('can_view_organization');

        $branchId = $request->filled('branch_id') ? (int) $request->integer('branch_id') : null;

        return ApiResponse::success(
            DepartmentResource::collection(
                $this->organization->listDepartments($branchId),
            )->resolve(),
        );
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $this->authorize('can_manage_organization');

        $department = $this->organization->createDepartment($request->validated());

        return ApiResponse::created(
            (new DepartmentResource($department))->resolve(),
            'Department created.',
        );
    }

    public function update(UpdateDepartmentRequest $request, int $department): JsonResponse
    {
        $this->authorize('can_manage_organization');

        $model = $this->organization->findDepartment($department);
        $model = $this->organization->updateDepartment($model, $request->validated());

        return ApiResponse::success(
            (new DepartmentResource($model))->resolve(),
            'Department updated.',
        );
    }

    public function destroy(int $department): JsonResponse
    {
        $this->authorize('can_manage_organization');

        $model = $this->organization->findDepartment($department);
        $this->organization->deleteDepartment($model);

        return ApiResponse::success(null, 'Department deleted.');
    }
}
