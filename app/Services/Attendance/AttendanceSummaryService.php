<?php

namespace App\Services\Attendance;

use App\Exceptions\DomainException;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use App\Services\Organization\CompanyContext;

/**
 * Compute-only attendance summary for Payroll consumption.
 * Does not write payroll tables.
 */
class AttendanceSummaryService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
    ) {}

    /**
     * @return array{
     *     employee_id: int,
     *     period_start: string,
     *     period_end: string,
     *     worked_minutes: int,
     *     late_minutes: int,
     *     overtime_minutes: int,
     *     days_present: int
     * }
     */
    public function summarize(User $actor, int $employeeId, string $periodStart, string $periodEnd): array
    {
        $companyId = $this->companyContext->id();
        $employee = Employee::query()->find($employeeId);

        if ($employee === null || $employee->company_id !== $companyId) {
            throw new DomainException(
                message: 'Employee does not belong to the current company.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 403,
            );
        }

        $ownOnly = ! $actor->can('can_approve_attendance')
            && ! $actor->can('can_manage_attendance');

        if ($ownOnly) {
            $linked = Employee::query()
                ->where('company_id', $companyId)
                ->where('user_id', $actor->id)
                ->first();

            if ($linked === null || $linked->id !== $employeeId) {
                throw new DomainException(
                    message: 'You may only view your own attendance summary.',
                    errorCode: 'FORBIDDEN',
                    status: 403,
                );
            }
        }

        return $this->aggregate($companyId, $employeeId, $periodStart, $periodEnd);
    }

    /**
     * Service-to-service summary for Payroll (no own-scope gate).
     *
     * @return array{
     *     employee_id: int,
     *     period_start: string,
     *     period_end: string,
     *     worked_minutes: int,
     *     late_minutes: int,
     *     overtime_minutes: int,
     *     days_present: int
     * }
     */
    public function summarizeForPayroll(int $employeeId, string $periodStart, string $periodEnd): array
    {
        $companyId = $this->companyContext->id();
        $employee = Employee::query()->find($employeeId);

        if ($employee === null || $employee->company_id !== $companyId) {
            throw new DomainException(
                message: 'Employee does not belong to the current company.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 403,
            );
        }

        return $this->aggregate($companyId, $employeeId, $periodStart, $periodEnd);
    }

    /**
     * @return array{
     *     employee_id: int,
     *     period_start: string,
     *     period_end: string,
     *     worked_minutes: int,
     *     late_minutes: int,
     *     overtime_minutes: int,
     *     days_present: int
     * }
     */
    private function aggregate(int $companyId, int $employeeId, string $periodStart, string $periodEnd): array
    {
        $rows = AttendanceRecord::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->whereDate('work_date', '>=', $periodStart)
            ->whereDate('work_date', '<=', $periodEnd)
            ->where('status', '!=', 'rejected')
            ->get();

        return [
            'employee_id' => $employeeId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'worked_minutes' => (int) $rows->sum('worked_minutes'),
            'late_minutes' => (int) $rows->sum('late_minutes'),
            'overtime_minutes' => (int) $rows->sum('overtime_minutes'),
            'days_present' => $rows
                ->filter(fn (AttendanceRecord $r) => $r->check_in_at !== null)
                ->unique(fn (AttendanceRecord $r) => $r->work_date->toDateString())
                ->count(),
        ];
    }
}
