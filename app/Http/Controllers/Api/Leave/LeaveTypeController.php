<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\StoreLeaveTypeRequest;
use App\Http\Requests\Leave\UpdateLeaveTypeRequest;
use App\Http\Resources\LeaveTypeResource;
use App\Models\LeaveType;
use App\Services\Leave\LeaveTypeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function __construct(
        private readonly LeaveTypeService $leaveTypes,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', LeaveType::class);

        $paginator = $this->leaveTypes->list(
            filters: $request->only(['search', 'is_active']),
            perPage: min((int) $request->integer('per_page', 20), 100),
        );

        return ApiResponse::paginated(
            $paginator->through(fn (LeaveType $type) => (new LeaveTypeResource($type))->resolve()),
        );
    }

    public function store(StoreLeaveTypeRequest $request): JsonResponse
    {
        $this->authorize('create', LeaveType::class);

        $type = $this->leaveTypes->create($request->validated());

        return ApiResponse::created(
            (new LeaveTypeResource($type))->resolve(),
            'Leave type created.',
        );
    }

    public function update(UpdateLeaveTypeRequest $request, int $leaveType): JsonResponse
    {
        $model = $this->leaveTypes->find($leaveType);
        $this->authorize('update', $model);

        $model = $this->leaveTypes->update($model, $request->validated());

        return ApiResponse::success(
            (new LeaveTypeResource($model))->resolve(),
            'Leave type updated.',
        );
    }
}
