<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\CheckInRequest;
use App\Http\Requests\Attendance\CheckOutRequest;
use App\Http\Requests\Attendance\LockAttendancePeriodRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use App\Services\Attendance\AttendanceService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceRecordController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendance,
    ) {}

    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $this->authorize('checkInOut', AttendanceRecord::class);

        $record = $this->attendance->checkIn($request->validated());

        return ApiResponse::created(
            (new AttendanceRecordResource($record))->resolve(),
            'Checked in.',
        );
    }

    public function checkOut(CheckOutRequest $request): JsonResponse
    {
        $this->authorize('checkInOut', AttendanceRecord::class);

        $record = $this->attendance->checkOut($request->validated());

        return ApiResponse::success(
            (new AttendanceRecordResource($record))->resolve(),
            'Checked out.',
        );
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $paginator = $this->attendance->listRecords(
            actor: $request->user(),
            filters: $request->only(['employee_id', 'date_from', 'date_to', 'status']),
            perPage: min((int) $request->integer('per_page', 20), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(
                fn (AttendanceRecord $row) => (new AttendanceRecordResource($row))->resolve(),
            ),
        );
    }

    public function show(Request $request, int $record): JsonResponse
    {
        $model = $this->attendance->findRecord($request->user(), $record);
        $this->authorize('view', $model);

        return ApiResponse::success(
            (new AttendanceRecordResource($model))->resolve(),
        );
    }

    public function approve(Request $request, int $record): JsonResponse
    {
        $model = $this->attendance->findRecord($request->user(), $record);
        $this->authorize('approve', $model);

        $model = $this->attendance->approveRecord($request->user(), $model);

        return ApiResponse::success(
            (new AttendanceRecordResource($model))->resolve(),
            'Attendance record approved.',
        );
    }

    public function lockPeriod(LockAttendancePeriodRequest $request): JsonResponse
    {
        $this->authorize('manage', AttendanceRecord::class);

        $count = $this->attendance->lockPeriod($request->validated());

        return ApiResponse::success(
            ['locked_count' => $count],
            'Attendance period locked.',
        );
    }
}
