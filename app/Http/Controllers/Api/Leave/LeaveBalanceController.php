<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\AdjustLeaveBalanceRequest;
use App\Models\LeaveBalance;
use App\Services\Leave\LeaveBalanceService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveBalanceController extends Controller
{
    public function __construct(
        private readonly LeaveBalanceService $balances,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', LeaveBalance::class);

        $employeeId = (int) $request->integer('employee_id');
        $yearQuery = $request->query('year');
        $year = is_scalar($yearQuery) && (string) $yearQuery !== ''
            ? (string) $yearQuery
            : (string) now()->year;

        if ($employeeId <= 0) {
            $employeeId = (int) ($request->user()?->employee->id ?? 0);
        }

        if ($employeeId <= 0) {
            return ApiResponse::error(
                message: 'employee_id is required.',
                status: 422,
                errorCode: 'VALIDATION_FAILED',
            );
        }

        $viewer = $request->user();
        $ownId = $viewer?->employee->id;
        if (
            $ownId !== $employeeId
            && ! $viewer?->can('can_manage_leave_balances')
            && ! $viewer?->can('can_approve_leave')
        ) {
            return ApiResponse::error(
                message: 'Forbidden.',
                status: 403,
                errorCode: 'FORBIDDEN',
            );
        }

        return ApiResponse::success(
            $this->balances->list($employeeId, $year),
        );
    }

    public function adjust(AdjustLeaveBalanceRequest $request): JsonResponse
    {
        $this->authorize('adjust', LeaveBalance::class);

        $balance = $this->balances->adjust($request->validated());

        return ApiResponse::success([
            'leave_type_id' => $balance->leave_type_id,
            'leave_type_name' => $balance->leaveType?->name,
            'period_key' => $balance->period_key,
            'entitled' => (float) $balance->entitled,
            'used' => (float) $balance->used,
            'pending' => (float) $balance->pending,
            'remaining' => $balance->remaining(),
        ], 'Leave balance adjusted.');
    }
}
