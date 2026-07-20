<?php

namespace App\Http\Controllers\Api\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Services\Organization\OrganizationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organization,
    ) {}

    public function current(): JsonResponse
    {
        $this->authorize('can_view_organization');

        return ApiResponse::success(
            (new CompanyResource($this->organization->currentCompany()))->resolve(),
        );
    }

    public function updateCurrent(UpdateCompanyRequest $request): JsonResponse
    {
        $this->authorize('can_manage_company');

        $company = $this->organization->updateCompany($request->validated());

        return ApiResponse::success(
            (new CompanyResource($company))->resolve(),
            'Company updated.',
        );
    }
}
