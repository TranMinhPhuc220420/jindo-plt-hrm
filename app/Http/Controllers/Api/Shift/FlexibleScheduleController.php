<?php

namespace App\Http\Controllers\Api\Shift;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shift\ReplaceFlexibleScheduleRequest;
use App\Http\Resources\ShiftAssignmentResource;
use App\Models\ShiftAssignment;
use App\Services\Shift\ShiftAssignmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class FlexibleScheduleController extends Controller
{
    public function __construct(
        private readonly ShiftAssignmentService $assignments,
    ) {}

    public function replace(ReplaceFlexibleScheduleRequest $request): JsonResponse
    {
        $this->authorize('create', ShiftAssignment::class);

        $validated = $request->validated();
        $rows = $this->assignments->replaceAdhocRange(
            (int) $validated['employee_id'],
            (string) $validated['date_from'],
            (string) $validated['date_to'],
            $validated['slots'],
        );

        return ApiResponse::success(
            array_map(
                fn (ShiftAssignment $row) => (new ShiftAssignmentResource($row))->resolve(),
                $rows,
            ),
            'Flexible schedule updated.',
        );
    }
}
