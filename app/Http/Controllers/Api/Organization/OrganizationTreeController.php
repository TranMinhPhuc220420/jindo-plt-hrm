<?php

namespace App\Http\Controllers\Api\Organization;

use App\Http\Controllers\Controller;
use App\Services\Organization\OrganizationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class OrganizationTreeController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organization,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->authorize('can_view_organization');

        return ApiResponse::success(
            $this->organization->tree(),
        );
    }
}
