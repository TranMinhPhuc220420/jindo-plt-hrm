<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Services\Attendance\AttendanceSummaryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceSummaryController extends Controller
{
    public function __construct(
        private readonly AttendanceSummaryService $summaries,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        if (! ($request->user()?->can('can_view_attendance') ?? false)) {
            throw new DomainException(
                message: 'You are not allowed to view attendance summaries.',
                errorCode: 'FORBIDDEN',
                status: 403,
            );
        }

        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $data = $this->summaries->summarize(
            actor: $request->user(),
            employeeId: (int) $validated['employee_id'],
            periodStart: $validated['period_start'],
            periodEnd: $validated['period_end'],
        );

        return ApiResponse::success($data);
    }
}
