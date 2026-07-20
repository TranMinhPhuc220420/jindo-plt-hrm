<?php

namespace App\Http\Controllers\Api\Performance;

use App\Http\Controllers\Controller;
use App\Http\Resources\PerformancePromotionSuggestionResource;
use App\Models\PerformancePromotionSuggestion;
use App\Services\Performance\PromotionSuggestionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionSuggestionController extends Controller
{
    public function __construct(
        private readonly PromotionSuggestionService $suggestions,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('can_view_promotion_suggestions');

        $paginator = $this->suggestions->list(
            filters: $request->only(['status', 'employee_id']),
            perPage: min((int) $request->integer('per_page', 20), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(fn (PerformancePromotionSuggestion $row) => (new PerformancePromotionSuggestionResource($row))->resolve()),
        );
    }

    public function acknowledge(Request $request, int $promotionSuggestion): JsonResponse
    {
        $this->authorize('can_view_promotion_suggestions');

        $model = $this->suggestions->acknowledge(
            $this->suggestions->find($promotionSuggestion),
            $request->user(),
        );

        return ApiResponse::success(
            (new PerformancePromotionSuggestionResource($model))->resolve(),
            'Promotion suggestion acknowledged.',
        );
    }
}
