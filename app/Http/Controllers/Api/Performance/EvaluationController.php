<?php

namespace App\Http\Controllers\Api\Performance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Performance\StoreEvaluationRequest;
use App\Http\Resources\PerformanceEvaluationResource;
use App\Models\PerformanceEvaluation;
use App\Services\Performance\EvaluationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function __construct(
        private readonly EvaluationService $evaluations,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('can_view_performance');

        $paginator = $this->evaluations->list(
            filters: $request->only(['review_cycle_id', 'employee_id']),
            perPage: min((int) $request->integer('per_page', 20), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(fn (PerformanceEvaluation $row) => (new PerformanceEvaluationResource($row))->resolve()),
        );
    }

    public function store(StoreEvaluationRequest $request): JsonResponse
    {
        $evaluation = $this->evaluations->submit($request->validated(), $request->user());

        return ApiResponse::created(
            (new PerformanceEvaluationResource($evaluation))->resolve(),
            'Evaluation submitted.',
        );
    }

    public function show(int $evaluation): JsonResponse
    {
        $this->authorize('can_view_performance');

        $model = $this->evaluations->find($evaluation);

        return ApiResponse::success(
            (new PerformanceEvaluationResource($model))->resolve(),
        );
    }
}
