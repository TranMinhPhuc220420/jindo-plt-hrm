<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\UpdateWeekendRulesRequest;
use App\Http\Resources\WeekendRuleResource;
use App\Models\WeekendRule;
use App\Services\Leave\WeekendRuleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class WeekendRuleController extends Controller
{
    public function __construct(
        private readonly WeekendRuleService $weekendRules,
    ) {}

    public function show(): JsonResponse
    {
        $this->authorize('viewAny', WeekendRule::class);

        $rule = $this->weekendRules->get();

        return ApiResponse::success(
            (new WeekendRuleResource($rule))->resolve(),
        );
    }

    public function update(UpdateWeekendRulesRequest $request): JsonResponse
    {
        $this->authorize('update', WeekendRule::class);

        $rule = $this->weekendRules->upsert($request->validated());

        return ApiResponse::success(
            (new WeekendRuleResource($rule))->resolve(),
            'Weekend rules updated.',
        );
    }
}
