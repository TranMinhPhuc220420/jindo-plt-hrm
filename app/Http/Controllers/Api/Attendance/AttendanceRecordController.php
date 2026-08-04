<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\BulkApproveAttendanceRecordsRequest;
use App\Http\Requests\Attendance\CheckInRequest;
use App\Http\Requests\Attendance\CheckOutRequest;
use App\Http\Requests\Attendance\LockAttendancePeriodRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use App\Services\Attendance\AttendancePunchIdempotencyService;
use App\Services\Attendance\AttendanceService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceRecordController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendance,
        private readonly AttendancePunchIdempotencyService $punchIdempotency,
    ) {}

    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $this->authorize('checkInOut', AttendanceRecord::class);

        $validated = $request->validated();

        return $this->punchIdempotency->checkIn(
            $request->header('Idempotency-Key'),
            [
                'worked_at' => $validated['worked_at'] ?? null,
                'note' => $validated['note'] ?? null,
                'source' => $validated['source'] ?? null,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'accuracy_meters' => $validated['accuracy_meters'] ?? null,
                'address' => $validated['address'],
                'captured_at' => $validated['captured_at'] ?? null,
                'photo' => $this->requireEvidencePhoto($request),
            ],
        );
    }

    public function checkOut(CheckOutRequest $request): JsonResponse
    {
        $this->authorize('checkInOut', AttendanceRecord::class);

        $validated = $request->validated();

        return $this->punchIdempotency->checkOut(
            $request->header('Idempotency-Key'),
            [
                'worked_at' => $validated['worked_at'] ?? null,
                'note' => $validated['note'] ?? null,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'accuracy_meters' => $validated['accuracy_meters'] ?? null,
                'address' => $validated['address'],
                'captured_at' => $validated['captured_at'] ?? null,
                'photo' => $this->requireEvidencePhoto($request),
            ],
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

    public function evidencePhoto(Request $request, int $record, string $punchType): StreamedResponse
    {
        $model = $this->attendance->findRecord($request->user(), $record);
        $this->authorize('view', $model);

        return $this->attendance->streamEvidencePhoto($request->user(), $model, $punchType);
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

    public function bulkApprove(BulkApproveAttendanceRecordsRequest $request): JsonResponse
    {
        $result = $this->attendance->approveRecords(
            $request->user(),
            $request->validated('ids'),
        );

        return ApiResponse::success(
            $result,
            'Attendance records approved.',
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

    private function requireEvidencePhoto(Request $request): UploadedFile
    {
        $photo = $request->file('photo');

        if (! $photo instanceof UploadedFile) {
            throw new DomainException(
                message: 'A camera photo is required to record attendance.',
                errorCode: 'ATTENDANCE_EVIDENCE_REQUIRED',
                status: 422,
            );
        }

        return $photo;
    }
}
