<?php

namespace App\Http\Controllers\Api\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreBranchRequest;
use App\Http\Requests\Organization\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Services\Organization\OrganizationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class BranchController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organization,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('can_view_organization');

        return ApiResponse::success(
            BranchResource::collection($this->organization->listBranches())->resolve(),
        );
    }

    public function store(StoreBranchRequest $request): JsonResponse
    {
        $this->authorize('can_manage_organization');

        $branch = $this->organization->createBranch($request->validated());

        return ApiResponse::created(
            (new BranchResource($branch))->resolve(),
            'Branch created.',
        );
    }

    public function show(int $branch): JsonResponse
    {
        $this->authorize('can_view_organization');

        $model = $this->organization->findBranch($branch);

        return ApiResponse::success(
            (new BranchResource($model))->resolve(),
        );
    }

    public function update(UpdateBranchRequest $request, int $branch): JsonResponse
    {
        $this->authorize('can_manage_organization');

        $model = $this->organization->findBranch($branch);
        $model = $this->organization->updateBranch($model, $request->validated());

        return ApiResponse::success(
            (new BranchResource($model))->resolve(),
            'Branch updated.',
        );
    }

    public function destroy(int $branch): JsonResponse
    {
        $this->authorize('can_manage_organization');

        $model = $this->organization->findBranch($branch);
        $this->organization->deleteBranch($model);

        return ApiResponse::success(null, 'Branch deleted.');
    }
}
