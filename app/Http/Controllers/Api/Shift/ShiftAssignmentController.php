<?php

namespace App\Http\Controllers\Api\Shift;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shift\StoreShiftAssignmentRequest;
use App\Http\Requests\Shift\UpdateShiftAssignmentRequest;
use App\Http\Resources\ShiftAssignmentResource;
use App\Models\ShiftAssignment;
use App\Services\Shift\ShiftAssignmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftAssignmentController extends Controller
{
    public function __construct(
        private readonly ShiftAssignmentService $assignments,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ShiftAssignment::class);

        $paginator = $this->assignments->list(
            filters: $request->only(['employee_id', 'shift_id']),
            perPage: min((int) $request->integer('per_page', 20), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(
                fn (ShiftAssignment $row) => (new ShiftAssignmentResource($row))->resolve(),
            ),
        );
    }

    public function store(StoreShiftAssignmentRequest $request): JsonResponse
    {
        $this->authorize('create', ShiftAssignment::class);

        $assignment = $this->assignments->create($request->validated());

        return ApiResponse::created(
            (new ShiftAssignmentResource($assignment))->resolve(),
            'Shift assignment created.',
        );
    }

    public function update(UpdateShiftAssignmentRequest $request, int $shiftAssignment): JsonResponse
    {
        $model = $this->assignments->find($shiftAssignment);
        $this->authorize('update', $model);

        $model = $this->assignments->update($model, $request->validated());

        return ApiResponse::success(
            (new ShiftAssignmentResource($model))->resolve(),
            'Shift assignment updated.',
        );
    }

    public function destroy(int $shiftAssignment): JsonResponse
    {
        $model = $this->assignments->find($shiftAssignment);
        $this->authorize('delete', $model);

        $this->assignments->delete($model);

        return ApiResponse::success(null, 'Shift assignment deleted.');
    }
}
