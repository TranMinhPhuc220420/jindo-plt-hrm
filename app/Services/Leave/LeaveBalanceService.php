<?php

namespace App\Services\Leave;

use App\Exceptions\DomainException;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use Illuminate\Support\Facades\DB;

class LeaveBalanceService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array<int, array{
     *     leave_type_id: int,
     *     leave_type_name: string,
     *     leave_type_code: string,
     *     period_key: string,
     *     entitled: float,
     *     used: float,
     *     pending: float,
     *     remaining: float
     * }>
     */
    public function list(int $employeeId, string $year): array
    {
        $companyId = $this->companyContext->id();
        $this->assertEmployeeInCompany($employeeId);

        $balances = LeaveBalance::query()
            ->with('leaveType')
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->where('period_key', $year)
            ->get();

        return $balances->map(function (LeaveBalance $balance): array {
            return [
                'leave_type_id' => $balance->leave_type_id,
                'leave_type_name' => $balance->leaveType->name ?? '',
                'leave_type_code' => $balance->leaveType->code ?? '',
                'period_key' => $balance->period_key,
                'entitled' => (float) $balance->entitled,
                'used' => (float) $balance->used,
                'pending' => (float) $balance->pending,
                'remaining' => $balance->remaining(),
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function adjust(array $data): LeaveBalance
    {
        $companyId = $this->companyContext->id();
        $employeeId = (int) $data['employee_id'];
        $leaveTypeId = (int) $data['leave_type_id'];
        $periodKey = (string) ($data['period_key'] ?? now()->year);
        $delta = (float) $data['delta'];

        $this->assertEmployeeInCompany($employeeId);
        $this->assertLeaveTypeInCompany($leaveTypeId);

        return DB::transaction(function () use ($companyId, $employeeId, $leaveTypeId, $periodKey, $delta, $data): LeaveBalance {
            $balance = LeaveBalance::query()->firstOrCreate(
                [
                    'employee_id' => $employeeId,
                    'leave_type_id' => $leaveTypeId,
                    'period_key' => $periodKey,
                ],
                [
                    'company_id' => $companyId,
                    'entitled' => 0,
                    'used' => 0,
                    'pending' => 0,
                ],
            );

            $balance->entitled = (float) $balance->entitled + $delta;
            $balance->save();

            $this->audit->write(
                action: 'leave.balance_adjusted',
                subject: $balance,
                payload: [
                    'employee_id' => $employeeId,
                    'leave_type_id' => $leaveTypeId,
                    'period_key' => $periodKey,
                    'delta' => $delta,
                    'note' => $data['note'] ?? null,
                    'entitled' => (float) $balance->entitled,
                ],
            );

            return $balance->fresh(['leaveType']);
        });
    }

    public function getOrCreate(int $employeeId, int $leaveTypeId, string $periodKey): LeaveBalance
    {
        $companyId = $this->companyContext->id();

        return LeaveBalance::query()->firstOrCreate(
            [
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'period_key' => $periodKey,
            ],
            [
                'company_id' => $companyId,
                'entitled' => 0,
                'used' => 0,
                'pending' => 0,
            ],
        );
    }

    private function assertEmployeeInCompany(int $employeeId): void
    {
        $employee = Employee::query()->find($employeeId);

        if ($employee === null || $employee->company_id !== $this->companyContext->id()) {
            throw new DomainException(
                message: 'Employee does not belong to the current company.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 403,
            );
        }
    }

    private function assertLeaveTypeInCompany(int $leaveTypeId): void
    {
        $type = LeaveType::query()->find($leaveTypeId);

        if ($type === null || $type->company_id !== $this->companyContext->id()) {
            throw new DomainException(
                message: 'Leave type does not belong to the current company.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 403,
            );
        }
    }
}
