<?php

namespace App\Http\Controllers\Api\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreTeamRequest;
use App\Http\Requests\Organization\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Services\Organization\OrganizationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organization,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('can_view_organization');

        $departmentId = $request->filled('department_id')
            ? (int) $request->integer('department_id')
            : null;

        return ApiResponse::success(
            TeamResource::collection(
                $this->organization->listTeams($departmentId),
            )->resolve(),
        );
    }

    public function store(StoreTeamRequest $request): JsonResponse
    {
        $this->authorize('can_manage_organization');

        $team = $this->organization->createTeam($request->validated());

        return ApiResponse::created(
            (new TeamResource($team))->resolve(),
            'Team created.',
        );
    }

    public function update(UpdateTeamRequest $request, int $team): JsonResponse
    {
        $this->authorize('can_manage_organization');

        $model = $this->organization->findTeam($team);
        $model = $this->organization->updateTeam($model, $request->validated());

        return ApiResponse::success(
            (new TeamResource($model))->resolve(),
            'Team updated.',
        );
    }

    public function destroy(int $team): JsonResponse
    {
        $this->authorize('can_manage_organization');

        $model = $this->organization->findTeam($team);
        $this->organization->deleteTeam($model);

        return ApiResponse::success(null, 'Team deleted.');
    }
}
