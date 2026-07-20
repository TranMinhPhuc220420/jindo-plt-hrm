<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\ApproveLeaveRequest;
use App\Http\Requests\Leave\RejectLeaveRequest;
use App\Http\Requests\Leave\StoreLeaveRequestRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Services\Leave\LeaveRequestService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $leaveRequests,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', LeaveRequest::class);

        $paginator = $this->leaveRequests->list(
            filters: $request->only(['employee_id', 'status', 'date_from', 'date_to']),
            perPage: min((int) $request->integer('per_page', 20), 100),
            viewer: $request->user(),
        );

        return ApiResponse::paginated(
            $paginator->through(fn (LeaveRequest $row) => (new LeaveRequestResource($row))->resolve()),
        );
    }

    public function store(StoreLeaveRequestRequest $request): JsonResponse
    {
        $this->authorize('create', LeaveRequest::class);

        $model = $this->leaveRequests->create($request->validated(), $request->user());

        return ApiResponse::created(
            (new LeaveRequestResource($model))->resolve(),
            'Leave request created.',
        );
    }

    public function show(int $leaveRequest): JsonResponse
    {
        $model = $this->leaveRequests->find($leaveRequest);
        $this->authorize('view', $model);

        return ApiResponse::success(
            (new LeaveRequestResource($model))->resolve(),
        );
    }

    public function cancel(int $leaveRequest): JsonResponse
    {
        $model = $this->leaveRequests->find($leaveRequest);
        $this->authorize('cancel', $model);

        $model = $this->leaveRequests->cancel($model, request()->user());

        return ApiResponse::success(
            (new LeaveRequestResource($model))->resolve(),
            'Leave request cancelled.',
        );
    }

    public function approve(ApproveLeaveRequest $request, int $leaveRequest): JsonResponse
    {
        $model = $this->leaveRequests->find($leaveRequest);
        $this->authorize('approve', $model);

        $model = $this->leaveRequests->approve($model, $request->user(), $request->validated());

        return ApiResponse::success(
            (new LeaveRequestResource($model))->resolve(),
            'Leave request approved.',
        );
    }

    public function reject(RejectLeaveRequest $request, int $leaveRequest): JsonResponse
    {
        $model = $this->leaveRequests->find($leaveRequest);
        $this->authorize('approve', $model);

        $model = $this->leaveRequests->reject($model, $request->user(), $request->validated());

        return ApiResponse::success(
            (new LeaveRequestResource($model))->resolve(),
            'Leave request rejected.',
        );
    }
}
