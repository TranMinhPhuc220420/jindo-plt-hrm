<?php

namespace App\Http\Controllers\Api\Shift;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shift\StoreShiftRequest;
use App\Http\Requests\Shift\UpdateShiftRequest;
use App\Http\Resources\ShiftResource;
use App\Models\Shift;
use App\Services\Shift\ShiftService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function __construct(
        private readonly ShiftService $shifts,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Shift::class);

        $paginator = $this->shifts->list(
            filters: $request->only(['search', 'kind', 'is_active']),
            perPage: min((int) $request->integer('per_page', 20), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(fn (Shift $shift) => (new ShiftResource($shift))->resolve()),
        );
    }

    public function store(StoreShiftRequest $request): JsonResponse
    {
        $this->authorize('create', Shift::class);

        $shift = $this->shifts->create($request->validated());

        return ApiResponse::created(
            (new ShiftResource($shift))->resolve(),
            'Shift created.',
        );
    }

    public function show(int $shift): JsonResponse
    {
        $model = $this->shifts->find($shift);
        $this->authorize('view', $model);

        return ApiResponse::success(
            (new ShiftResource($model))->resolve(),
        );
    }

    public function update(UpdateShiftRequest $request, int $shift): JsonResponse
    {
        $model = $this->shifts->find($shift);
        $this->authorize('update', $model);

        $model = $this->shifts->update($model, $request->validated());

        return ApiResponse::success(
            (new ShiftResource($model))->resolve(),
            'Shift updated.',
        );
    }

    public function destroy(int $shift): JsonResponse
    {
        $model = $this->shifts->find($shift);
        $this->authorize('delete', $model);

        $this->shifts->delete($model);

        return ApiResponse::success(null, 'Shift deleted.');
    }
}
