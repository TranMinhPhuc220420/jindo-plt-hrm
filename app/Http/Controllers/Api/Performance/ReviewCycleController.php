<?php

namespace App\Http\Controllers\Api\Performance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Performance\StoreReviewCycleRequest;
use App\Http\Requests\Performance\SyncReviewCycleParticipantsRequest;
use App\Http\Resources\PerformanceReviewCycleResource;
use App\Models\PerformanceReviewCycle;
use App\Services\Performance\ReviewCycleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewCycleController extends Controller
{
    public function __construct(
        private readonly ReviewCycleService $cycles,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('can_view_performance');

        $paginator = $this->cycles->list(
            actor: $request->user(),
            filters: $request->only(['status']),
            perPage: min((int) $request->integer('per_page', 20), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(fn (PerformanceReviewCycle $row) => (new PerformanceReviewCycleResource($row))->resolve()),
        );
    }

    public function store(StoreReviewCycleRequest $request): JsonResponse
    {
        $cycle = $this->cycles->create($request->validated(), $request->user());

        return ApiResponse::created(
            (new PerformanceReviewCycleResource($cycle))->resolve(),
            'Review cycle created.',
        );
    }

    public function show(Request $request, int $reviewCycle): JsonResponse
    {
        $this->authorize('can_view_performance');

        $cycle = $this->cycles->find($reviewCycle, $request->user());

        return ApiResponse::success(
            (new PerformanceReviewCycleResource($cycle))->resolve(),
        );
    }

    public function syncParticipants(SyncReviewCycleParticipantsRequest $request, int $reviewCycle): JsonResponse
    {
        $cycle = $this->cycles->syncParticipants(
            $this->cycles->find($reviewCycle, $request->user()),
            $request->validated('participant_employee_ids'),
        );

        return ApiResponse::success(
            (new PerformanceReviewCycleResource($cycle))->resolve(),
            'Review cycle participants updated.',
        );
    }

    public function start(Request $request, int $reviewCycle): JsonResponse
    {
        $this->authorize('can_manage_review_cycles');

        $cycle = $this->cycles->start($this->cycles->find($reviewCycle, $request->user()));

        return ApiResponse::success(
            (new PerformanceReviewCycleResource($cycle))->resolve(),
            'Review cycle started.',
        );
    }

    public function finalize(Request $request, int $reviewCycle): JsonResponse
    {
        $this->authorize('can_manage_review_cycles');

        $cycle = $this->cycles->finalize($this->cycles->find($reviewCycle, $request->user()));

        return ApiResponse::success(
            (new PerformanceReviewCycleResource($cycle))->resolve(),
            'Review cycle finalized.',
        );
    }

    public function destroy(Request $request, int $reviewCycle): JsonResponse
    {
        $this->authorize('can_manage_review_cycles');

        $this->cycles->delete($this->cycles->find($reviewCycle, $request->user()));

        return ApiResponse::success(null, 'Review cycle deleted.');
    }
}
