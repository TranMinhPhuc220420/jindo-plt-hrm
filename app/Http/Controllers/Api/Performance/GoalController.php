<?php

namespace App\Http\Controllers\Api\Performance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Performance\StoreGoalRequest;
use App\Http\Requests\Performance\UpdateGoalRequest;
use App\Http\Resources\PerformanceGoalResource;
use App\Models\PerformanceGoal;
use App\Services\Performance\GoalService;
use App\Services\Performance\ReviewCycleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    public function __construct(
        private readonly GoalService $goals,
        private readonly ReviewCycleService $cycles,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('can_view_performance');

        if ($request->filled('review_cycle_id')) {
            $this->cycles->find((int) $request->integer('review_cycle_id'), $request->user());
        }

        $paginator = $this->goals->list(
            filters: $request->only(['employee_id', 'review_cycle_id', 'status']),
            perPage: min((int) $request->integer('per_page', 20), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(fn (PerformanceGoal $row) => (new PerformanceGoalResource($row))->resolve()),
        );
    }

    public function store(StoreGoalRequest $request): JsonResponse
    {
        $goal = $this->goals->create($request->validated(), $request->user());

        return ApiResponse::created(
            (new PerformanceGoalResource($goal))->resolve(),
            'Goal created.',
        );
    }

    public function update(UpdateGoalRequest $request, int $goal): JsonResponse
    {
        $model = $this->goals->update($this->goals->find($goal), $request->validated());

        return ApiResponse::success(
            (new PerformanceGoalResource($model))->resolve(),
            'Goal updated.',
        );
    }
}
