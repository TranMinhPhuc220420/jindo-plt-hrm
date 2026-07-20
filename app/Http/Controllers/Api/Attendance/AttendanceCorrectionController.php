<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StoreAttendanceCorrectionRequest;
use App\Http\Resources\AttendanceCorrectionResource;
use App\Models\AttendanceCorrection;
use App\Services\Attendance\AttendanceService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceCorrectionController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendance,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AttendanceCorrection::class);

        $paginator = $this->attendance->listCorrections(
            actor: $request->user(),
            filters: $request->only(['employee_id', 'status']),
            perPage: min((int) $request->integer('per_page', 20), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(
                fn (AttendanceCorrection $row) => (new AttendanceCorrectionResource($row))->resolve(),
            ),
        );
    }

    public function store(StoreAttendanceCorrectionRequest $request): JsonResponse
    {
        $this->authorize('create', AttendanceCorrection::class);

        $correction = $this->attendance->requestCorrection(
            $request->user(),
            $request->validated(),
        );

        return ApiResponse::created(
            (new AttendanceCorrectionResource($correction))->resolve(),
            'Correction requested.',
        );
    }

    public function approve(Request $request, int $correction): JsonResponse
    {
        $model = $this->attendance->findCorrection($correction);
        $this->authorize('approve', $model);

        $model = $this->attendance->approveCorrection($request->user(), $model);

        return ApiResponse::success(
            (new AttendanceCorrectionResource($model))->resolve(),
            'Correction approved.',
        );
    }

    public function reject(Request $request, int $correction): JsonResponse
    {
        $model = $this->attendance->findCorrection($correction);
        $this->authorize('approve', $model);

        $note = $request->validate([
            'review_note' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ])['review_note'] ?? null;

        $model = $this->attendance->rejectCorrection($request->user(), $model, $note);

        return ApiResponse::success(
            (new AttendanceCorrectionResource($model))->resolve(),
            'Correction rejected.',
        );
    }
}
