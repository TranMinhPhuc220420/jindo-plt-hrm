<?php

namespace App\Http\Controllers\Api\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StorePositionRequest;
use App\Http\Requests\Organization\UpdatePositionRequest;
use App\Http\Resources\PositionResource;
use App\Services\Organization\OrganizationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PositionController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organization,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('can_view_organization');

        return ApiResponse::success(
            PositionResource::collection($this->organization->listPositions())->resolve(),
        );
    }

    public function store(StorePositionRequest $request): JsonResponse
    {
        $this->authorize('can_manage_organization');

        $position = $this->organization->createPosition($request->validated());

        return ApiResponse::created(
            (new PositionResource($position))->resolve(),
            'Position created.',
        );
    }

    public function update(UpdatePositionRequest $request, int $position): JsonResponse
    {
        $this->authorize('can_manage_organization');

        $model = $this->organization->findPosition($position);
        $model = $this->organization->updatePosition($model, $request->validated());

        return ApiResponse::success(
            (new PositionResource($model))->resolve(),
            'Position updated.',
        );
    }

    public function destroy(int $position): JsonResponse
    {
        $this->authorize('can_manage_organization');

        $model = $this->organization->findPosition($position);
        $this->organization->deletePosition($model);

        return ApiResponse::success(null, 'Position deleted.');
    }
}
