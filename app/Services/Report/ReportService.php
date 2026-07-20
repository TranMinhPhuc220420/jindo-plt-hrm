<?php

namespace App\Services\Report;

use App\Exceptions\DomainException;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\PerformanceEvaluation;
use App\Models\User;
use App\Services\Organization\CompanyContext;

/**
 * Read-oriented, cross-domain reports. Never a write path for domain data.
 */
class ReportService
{
    /** @var array<string, string> */
    private const PERMISSIONS = [
        'attendance' => 'can_view_attendance_reports',
        'leave' => 'can_view_leave_reports',
        'employees' => 'can_view_employee_reports',
        'departments' => 'can_view_employee_reports',
        'payroll' => 'can_view_payroll_reports',
        'performance' => 'can_view_performance_reports',
    ];

    public function __construct(
        private readonly CompanyContext $companyContext,
    ) {}

    /**
     * @return list<string>
     */
    public static function availableReports(): array
    {
        return array_keys(self::PERMISSIONS);
    }

    /**
     * Generate report rows, enforcing the report's permission gate.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function generate(string $report, array $filters, User $actor): array
    {
        if (! array_key_exists($report, self::PERMISSIONS)) {
            throw new DomainException(
                message: 'Unknown report type.',
                errorCode: 'REPORT_FILTER_INVALID',
                status: 422,
            );
        }

        if (! $actor->can(self::PERMISSIONS[$report])) {
            throw new DomainException(
                message: 'You do not have permission to view this report.',
                errorCode: 'REPORT_FORBIDDEN',
                status: 403,
            );
        }

        return match ($report) {
            'attendance' => $this->attendance($filters),
            'leave' => $this->leave($filters),
            'employees' => $this->employees($filters),
            'departments' => $this->departments($filters),
            'payroll' => $this->payroll($filters),
            'performance' => $this->performance($filters),
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function attendance(array $filters): array
    {
        $companyId = $this->companyContext->id();

        $query = AttendanceRecord::query()
            ->with('employee')
            ->where('company_id', $companyId)
            ->where('status', '!=', 'rejected');

        if (! empty($filters['date_from'])) {
            $query->whereDate('work_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('work_date', '<=', $filters['date_to']);
        }
        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', (int) $filters['employee_id']);
        }

        $grouped = $query->get()->groupBy('employee_id');

        $rows = [];
        foreach ($grouped as $employeeId => $records) {
            $employee = $records->first()->employee;
            $rows[] = [
                'employee_id' => (int) $employeeId,
                'employee_name' => $employee->full_name ?? $employee?->code,
                'present_days' => $records->filter(fn (AttendanceRecord $r) => $r->check_in_at !== null)->count(),
                'late_minutes' => (int) $records->sum('late_minutes'),
                'overtime_minutes' => (int) $records->sum('overtime_minutes'),
                'worked_minutes' => (int) $records->sum('worked_minutes'),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function leave(array $filters): array
    {
        $companyId = $this->companyContext->id();

        $query = LeaveRequest::query()
            ->with(['employee', 'leaveType'])
            ->where('company_id', $companyId)
            ->orderByDesc('id');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('end_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('start_date', '<=', $filters['date_to']);
        }

        return $query->get()->map(fn (LeaveRequest $r) => [
            'leave_request_id' => $r->id,
            'employee_id' => $r->employee_id,
            'employee_name' => $r->employee->full_name ?? $r->employee?->code,
            'leave_type' => $r->leaveType?->name,
            'leave_type_code' => $r->leaveType?->code,
            'start_date' => $r->start_date->toDateString(),
            'end_date' => $r->end_date->toDateString(),
            'quantity' => (float) $r->quantity,
            'status' => $r->status,
        ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function employees(array $filters): array
    {
        $companyId = $this->companyContext->id();

        $query = Employee::query()
            ->with(['department'])
            ->where('company_id', $companyId)
            ->orderBy('id');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['department_id'])) {
            $query->where('department_id', (int) $filters['department_id']);
        }

        return $query->get()->map(fn (Employee $e) => [
            'employee_id' => $e->id,
            'code' => $e->code,
            'employee_name' => $e->full_name,
            'department' => $e->department?->name,
            'status' => $e->status,
            'hired_at' => $e->hired_at->toDateString(),
        ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function departments(array $filters): array
    {
        $companyId = $this->companyContext->id();

        $departments = Department::query()
            ->where('company_id', $companyId)
            ->orderBy('id')
            ->get();

        $headcounts = Employee::query()
            ->where('company_id', $companyId)
            ->whereNotNull('department_id')
            ->selectRaw('department_id, count(*) as total')
            ->groupBy('department_id')
            ->pluck('total', 'department_id');

        return $departments->map(fn (Department $d) => [
            'department_id' => $d->id,
            'department' => $d->name,
            'code' => $d->code,
            'headcount' => (int) ($headcounts[$d->id] ?? 0),
        ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function payroll(array $filters): array
    {
        $companyId = $this->companyContext->id();

        $query = PayrollRun::query()
            ->where('company_id', $companyId)
            ->orderByDesc('id');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('period_end', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('period_start', '<=', $filters['date_to']);
        }

        return $query->get()->map(fn (PayrollRun $r) => [
            'payroll_run_id' => $r->id,
            'name' => $r->name,
            'period_start' => $r->period_start->toDateString(),
            'period_end' => $r->period_end->toDateString(),
            'status' => $r->status,
            'employee_count' => (int) $r->employee_count,
            'total_gross' => (float) $r->total_gross,
            'total_net' => (float) $r->total_net,
        ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function performance(array $filters): array
    {
        $companyId = $this->companyContext->id();

        $query = PerformanceEvaluation::query()
            ->with(['employee', 'reviewCycle'])
            ->where('company_id', $companyId)
            ->orderByDesc('id');

        if (! empty($filters['review_cycle_id'])) {
            $query->where('review_cycle_id', (int) $filters['review_cycle_id']);
        }
        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', (int) $filters['employee_id']);
        }

        return $query->get()->map(fn (PerformanceEvaluation $e) => [
            'evaluation_id' => $e->id,
            'employee_id' => $e->employee_id,
            'employee_name' => $e->employee->full_name ?? $e->employee?->code,
            'review_cycle' => $e->reviewCycle?->name,
            'overall_score' => (float) $e->overall_score,
            'submitted_at' => $e->submitted_at->toIso8601String(),
        ])->values()->all();
    }
}
