<?php

namespace App\Services\Leave;

use App\Events\LeaveApproved;
use App\Events\LeaveCancelled;
use App\Events\LeaveRejected;
use App\Events\LeaveRequested;
use App\Exceptions\DomainException;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeaveRequestService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
        private readonly LeaveDurationCalculator $duration,
        private readonly LeaveBalanceService $balances,
    ) {}

    /**
     * @param  array{employee_id?: int, status?: string, date_from?: string, date_to?: string}  $filters
     * @return LengthAwarePaginator<int, LeaveRequest>
     */
    public function list(array $filters = [], int $perPage = 20, ?User $viewer = null): LengthAwarePaginator
    {
        $companyId = $this->companyContext->id();
        $viewer ??= Auth::user();

        $query = LeaveRequest::query()
            ->with(['leaveType', 'employee'])
            ->where('company_id', $companyId)
            ->orderByDesc('id');

        if ($viewer !== null && ! $this->canViewAll($viewer)) {
            $ownEmployeeId = $viewer->employee?->id;
            if ($ownEmployeeId === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('employee_id', $ownEmployeeId);
            }
        } elseif (! empty($filters['employee_id'])) {
            $query->where('employee_id', (int) $filters['employee_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('end_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('start_date', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): LeaveRequest
    {
        $request = LeaveRequest::query()
            ->with(['leaveType', 'employee', 'reviewer'])
            ->where('company_id', $this->companyContext->id())
            ->find($id);

        if ($request === null) {
            throw new DomainException(
                message: 'Leave request not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        return $request;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): LeaveRequest
    {
        $companyId = $this->companyContext->id();
        $employee = $this->resolveRequesterEmployee($actor, $data['employee_id'] ?? null);
        $leaveType = $this->requireLeaveType((int) $data['leave_type_id']);

        $unit = $data['unit'] ?? $leaveType->unit_default ?? 'day';
        $isHalfDay = (bool) ($data['is_half_day'] ?? ($unit === 'half_day'));
        $startDate = CarbonImmutable::parse($data['start_date'])->toDateString();
        $endDate = CarbonImmutable::parse($data['end_date'] ?? $data['start_date'])->toDateString();

        if ($endDate < $startDate) {
            throw new DomainException(
                message: 'end_date must be on or after start_date.',
                errorCode: 'LEAVE_INVALID_DATES',
                status: 422,
            );
        }

        if ($isHalfDay || $unit === 'half_day') {
            $endDate = $startDate;
            $unit = 'half_day';
            $isHalfDay = true;
        }

        $quantity = $this->duration->calculate([
            'unit' => $unit,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_half_day' => $isHalfDay,
            'start_at' => $data['start_at'] ?? null,
            'end_at' => $data['end_at'] ?? null,
            'employee_id' => $employee->id,
        ]);

        if ($quantity <= 0) {
            throw new DomainException(
                message: 'Leave range has no deductible working time (holidays/weekends).',
                errorCode: 'LEAVE_INVALID_DATES',
                status: 422,
            );
        }

        $this->assertNoOverlap($employee->id, $startDate, $endDate);

        $periodKey = CarbonImmutable::parse($startDate)->format('Y');

        return DB::transaction(function () use (
            $companyId,
            $employee,
            $leaveType,
            $unit,
            $startDate,
            $endDate,
            $isHalfDay,
            $quantity,
            $periodKey,
            $data,
        ): LeaveRequest {
            if ($leaveType->requires_balance) {
                $balance = $this->balances->getOrCreate($employee->id, $leaveType->id, $periodKey);
                $remaining = $balance->remaining();

                if (! $leaveType->allows_negative && $remaining < $quantity) {
                    throw new DomainException(
                        message: 'Insufficient leave balance.',
                        errorCode: 'LEAVE_BALANCE_INSUFFICIENT',
                        status: 422,
                    );
                }

                $balance->pending = (float) $balance->pending + $quantity;
                $balance->save();
            }

            $request = LeaveRequest::query()->create([
                'company_id' => $companyId,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'unit' => $unit,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'start_at' => $data['start_at'] ?? null,
                'end_at' => $data['end_at'] ?? null,
                'is_half_day' => $isHalfDay,
                'half_day_period' => $data['half_day_period'] ?? null,
                'quantity' => $quantity,
                'reason' => $data['reason'] ?? null,
                'status' => 'pending',
            ]);

            $this->audit->write(
                action: 'leave.requested',
                subject: $request,
                payload: [
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'quantity' => $quantity,
                ],
            );

            LeaveRequested::dispatch($request);

            return $request->fresh(['leaveType', 'employee']);
        });
    }

    public function cancel(LeaveRequest $request, User $actor): LeaveRequest
    {
        if ($request->status !== 'pending') {
            throw new DomainException(
                message: 'Only pending leave requests can be cancelled.',
                errorCode: 'LEAVE_INVALID_TRANSITION',
                status: 422,
            );
        }

        $ownId = $actor->employee?->id;
        if ($ownId !== $request->employee_id && ! $actor->can('can_manage_leave_balances')) {
            throw new DomainException(
                message: 'You can only cancel your own leave requests.',
                errorCode: 'FORBIDDEN',
                status: 403,
            );
        }

        return DB::transaction(function () use ($request): LeaveRequest {
            $this->releasePending($request);
            $request->status = 'cancelled';
            $request->save();

            $this->audit->write(
                action: 'leave.cancelled',
                subject: $request,
                payload: ['quantity' => (float) $request->quantity],
            );

            LeaveCancelled::dispatch($request);

            return $request->fresh(['leaveType', 'employee']);
        });
    }

    /**
     * @param  array{note?: string}  $data
     */
    public function approve(LeaveRequest $request, User $actor, array $data = []): LeaveRequest
    {
        if ($request->status !== 'pending') {
            throw new DomainException(
                message: 'Only pending leave requests can be approved.',
                errorCode: 'LEAVE_INVALID_TRANSITION',
                status: 422,
            );
        }

        return DB::transaction(function () use ($request, $actor, $data): LeaveRequest {
            $leaveType = $request->leaveType ?? $this->requireLeaveType($request->leave_type_id);

            if ($leaveType->requires_balance) {
                $periodKey = CarbonImmutable::parse($request->start_date)->format('Y');
                $balance = $this->balances->getOrCreate(
                    $request->employee_id,
                    $request->leave_type_id,
                    $periodKey,
                );

                $quantity = (float) $request->quantity;
                $pending = (float) $balance->pending;

                if ($pending < $quantity) {
                    // Re-check remaining if pending was not reserved (legacy/edge)
                    $remaining = (float) $balance->entitled - (float) $balance->used;
                    if (! $leaveType->allows_negative && $remaining < $quantity) {
                        throw new DomainException(
                            message: 'Insufficient leave balance.',
                            errorCode: 'LEAVE_BALANCE_INSUFFICIENT',
                            status: 422,
                        );
                    }
                }

                $balance->pending = max(0, $pending - $quantity);
                $balance->used = (float) $balance->used + $quantity;
                $balance->save();
            }

            $request->status = 'approved';
            $request->reviewed_by = max(0, $actor->id);
            $request->reviewed_at = now();
            $request->review_note = $data['note'] ?? null;
            $request->save();

            $this->audit->write(
                action: 'leave.approved',
                subject: $request,
                payload: ['quantity' => (float) $request->quantity, 'note' => $data['note'] ?? null],
            );

            LeaveApproved::dispatch($request);

            return $request->fresh(['leaveType', 'employee', 'reviewer']);
        });
    }

    /**
     * @param  array{reason?: string}  $data
     */
    public function reject(LeaveRequest $request, User $actor, array $data = []): LeaveRequest
    {
        if ($request->status !== 'pending') {
            throw new DomainException(
                message: 'Only pending leave requests can be rejected.',
                errorCode: 'LEAVE_INVALID_TRANSITION',
                status: 422,
            );
        }

        return DB::transaction(function () use ($request, $actor, $data): LeaveRequest {
            $this->releasePending($request);

            $request->status = 'rejected';
            $request->reviewed_by = max(0, $actor->id);
            $request->reviewed_at = now();
            $request->review_note = $data['reason'] ?? null;
            $request->save();

            $this->audit->write(
                action: 'leave.rejected',
                subject: $request,
                payload: ['reason' => $data['reason'] ?? null],
            );

            LeaveRejected::dispatch($request);

            return $request->fresh(['leaveType', 'employee', 'reviewer']);
        });
    }

    private function releasePending(LeaveRequest $request): void
    {
        $leaveType = $request->leaveType ?? LeaveType::query()->find($request->leave_type_id);

        if ($leaveType === null || ! $leaveType->requires_balance) {
            return;
        }

        $periodKey = CarbonImmutable::parse($request->start_date)->format('Y');
        $balance = $this->balances->getOrCreate(
            $request->employee_id,
            $request->leave_type_id,
            $periodKey,
        );

        $balance->pending = max(0, (float) $balance->pending - (float) $request->quantity);
        $balance->save();
    }

    private function assertNoOverlap(int $employeeId, string $startDate, string $endDate): void
    {
        $overlap = LeaveRequest::query()
            ->where('company_id', $this->companyContext->id())
            ->where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->exists();

        if ($overlap) {
            throw new DomainException(
                message: 'Leave request overlaps an existing pending or approved request.',
                errorCode: 'LEAVE_OVERLAPPING_REQUEST',
                status: 422,
            );
        }
    }

    private function resolveRequesterEmployee(User $actor, mixed $employeeId): Employee
    {
        $companyId = $this->companyContext->id();

        if ($employeeId !== null && $actor->can('can_manage_leave_balances')) {
            $employee = Employee::query()->find((int) $employeeId);
            if ($employee === null || $employee->company_id !== $companyId) {
                throw new DomainException(
                    message: 'Employee does not belong to the current company.',
                    errorCode: 'COMPANY_SCOPE_MISMATCH',
                    status: 403,
                );
            }

            return $employee;
        }

        $employee = $actor->employee;
        if ($employee === null || $employee->company_id !== $companyId) {
            throw new DomainException(
                message: 'No employee profile linked to the current user.',
                errorCode: 'FORBIDDEN',
                status: 403,
            );
        }

        return $employee;
    }

    private function requireLeaveType(int $id): LeaveType
    {
        $type = LeaveType::query()
            ->where('company_id', $this->companyContext->id())
            ->where('is_active', true)
            ->find($id);

        if ($type === null) {
            throw new DomainException(
                message: 'Leave type not found.',
                errorCode: 'NOT_FOUND',
                status: 404,
            );
        }

        return $type;
    }

    private function canViewAll(User $user): bool
    {
        return $user->can('can_approve_leave')
            || $user->can('can_manage_leave_types')
            || $user->can('can_manage_leave_balances');
    }
}
