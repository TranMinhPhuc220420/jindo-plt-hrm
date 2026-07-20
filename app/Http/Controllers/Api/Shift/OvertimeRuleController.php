<?php

namespace App\Http\Controllers\Api\Shift;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shift\ReplaceOvertimeRulesRequest;
use App\Http\Resources\OvertimeRuleResource;
use App\Models\OvertimeRule;
use App\Services\Shift\OvertimeRuleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class OvertimeRuleController extends Controller
{
    public function __construct(
        private readonly OvertimeRuleService $rules,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', OvertimeRule::class);

        $rows = $this->rules->list();

        return ApiResponse::success(
            $rows->map(fn (OvertimeRule $rule) => (new OvertimeRuleResource($rule))->resolve())->all(),
        );
    }

    public function replace(ReplaceOvertimeRulesRequest $request): JsonResponse
    {
        $this->authorize('manage', OvertimeRule::class);

        $rows = $this->rules->replace($request->validated('rules'));

        return ApiResponse::success(
            $rows->map(fn (OvertimeRule $rule) => (new OvertimeRuleResource($rule))->resolve())->all(),
            'Overtime rules updated.',
        );
    }
}
