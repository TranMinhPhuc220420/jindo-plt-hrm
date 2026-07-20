<?php

namespace App\Services\Payroll;

use App\Events\SalaryChanged;
use App\Exceptions\DomainException;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EmployeeSalaryService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{employee_id?: int, current_only?: bool}  $filters
     * @return LengthAwarePaginator<int, EmployeeSalary>
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = EmployeeSalary::query()
            ->with('employee')
            ->where('company_id', $this->companyContext->id())
            ->orderByDesc('effective_from')
            ->orderByDesc('id');

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', (int) $filters['employee_id']);
        }

        if (! empty($filters['current_only'])) {
            $query->whereNull('effective_to');
        }

        return $query->paginate($perPage);
    }

    /**
     * @param  array{amount: mixed, currency?: string, effective_from: string, strategy?: string}  $data
     */
    public function upsert(int $employeeId, array $data): EmployeeSalary
    {
        $companyId = $this->companyContext->id();
        $employee = $this->requireEmployee($employeeId);
        $strategy = $data['strategy'] ?? 'monthly';

        if ($strategy !== 'monthly') {
            throw new DomainException(
                message: 'Only monthly salary strategy is supported in v1.',
                errorCode: 'VALIDATION_FAILED',
                status: 422,
            );
        }

        $effectiveFrom = CarbonImmutable::parse($data['effective_from'])->toDateString();

        return DB::transaction(function () use ($companyId, $employee, $data, $strategy, $effectiveFrom): EmployeeSalary {
            EmployeeSalary::query()
                ->where('company_id', $companyId)
                ->where('employee_id', $employee->id)
                ->whereNull('effective_to')
                ->whereDate('effective_from', '<', $effectiveFrom)
                ->update(['effective_to' => CarbonImmutable::parse($effectiveFrom)->subDay()->toDateString()]);

            $salary = EmployeeSalary::query()->updateOrCreate(
                [
                    'company_id' => $companyId,
                    'employee_id' => $employee->id,
                    'effective_from' => $effectiveFrom,
                ],
                [
                    'amount' => $data['amount'],
                    'currency' => $data['currency'] ?? 'VND',
                    'strategy' => $strategy,
                    'effective_to' => null,
                ],
            );

            $this->audit->write(
                action: 'payroll.salary_changed',
                subject: $salary,
                payload: [
                    'employee_id' => $employee->id,
                    'amount' => (string) $salary->amount,
                    'currency' => $salary->currency,
                    'effective_from' => $effectiveFrom,
                    'strategy' => $strategy,
                ],
            );

            SalaryChanged::dispatch($salary);

            return $salary->fresh(['employee']);
        });
    }

    public function effectiveSalary(int $employeeId, string $asOfDate): ?EmployeeSalary
    {
        return EmployeeSalary::query()
            ->where('company_id', $this->companyContext->id())
            ->where('employee_id', $employeeId)
            ->whereDate('effective_from', '<=', $asOfDate)
            ->where(function ($q) use ($asOfDate): void {
                $q->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $asOfDate);
            })
            ->orderByDesc('effective_from')
            ->first();
    }

    private function requireEmployee(int $employeeId): Employee
    {
        $employee = Employee::query()->find($employeeId);

        if ($employee === null || $employee->company_id !== $this->companyContext->id()) {
            throw new DomainException(
                message: 'Employee does not belong to the current company.',
                errorCode: 'COMPANY_SCOPE_MISMATCH',
                status: 403,
            );
        }

        return $employee;
    }
}
