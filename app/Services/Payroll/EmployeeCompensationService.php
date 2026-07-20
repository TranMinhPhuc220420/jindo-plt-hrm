<?php

namespace App\Services\Payroll;

use App\Exceptions\DomainException;
use App\Models\Employee;
use App\Models\EmployeeAllowance;
use App\Models\EmployeeBonus;
use App\Models\EmployeeDeduction;
use App\Services\Audit\AuditLogger;
use App\Services\Organization\CompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EmployeeCompensationService
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return Collection<int, EmployeeAllowance>
     */
    public function listAllowances(int $employeeId): Collection
    {
        $this->requireEmployee($employeeId);

        return EmployeeAllowance::query()
            ->where('company_id', $this->companyContext->id())
            ->where('employee_id', $employeeId)
            ->orderBy('code')
            ->get();
    }

    /**
     * @param  list<array{code: string, name: string, amount: mixed, is_taxable?: bool, is_active?: bool}>  $items
     * @return Collection<int, EmployeeAllowance>
     */
    public function replaceAllowances(int $employeeId, array $items): Collection
    {
        return $this->replaceComponents(
            employeeId: $employeeId,
            items: $items,
            modelClass: EmployeeAllowance::class,
            auditAction: 'payroll.allowances_replaced',
        );
    }

    /**
     * @return Collection<int, EmployeeDeduction>
     */
    public function listDeductions(int $employeeId): Collection
    {
        $this->requireEmployee($employeeId);

        return EmployeeDeduction::query()
            ->where('company_id', $this->companyContext->id())
            ->where('employee_id', $employeeId)
            ->orderBy('code')
            ->get();
    }

    /**
     * @param  list<array{code: string, name: string, amount: mixed, is_taxable?: bool, is_active?: bool}>  $items
     * @return Collection<int, EmployeeDeduction>
     */
    public function replaceDeductions(int $employeeId, array $items): Collection
    {
        return $this->replaceComponents(
            employeeId: $employeeId,
            items: $items,
            modelClass: EmployeeDeduction::class,
            auditAction: 'payroll.deductions_replaced',
        );
    }

    /**
     * @return Collection<int, EmployeeBonus>
     */
    public function listBonuses(int $employeeId): Collection
    {
        $this->requireEmployee($employeeId);

        return EmployeeBonus::query()
            ->where('company_id', $this->companyContext->id())
            ->where('employee_id', $employeeId)
            ->orderBy('code')
            ->get();
    }

    /**
     * @param  list<array{code: string, name: string, amount: mixed, is_taxable?: bool, is_active?: bool}>  $items
     * @return Collection<int, EmployeeBonus>
     */
    public function replaceBonuses(int $employeeId, array $items): Collection
    {
        return $this->replaceComponents(
            employeeId: $employeeId,
            items: $items,
            modelClass: EmployeeBonus::class,
            auditAction: 'payroll.bonuses_replaced',
        );
    }

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @param  list<array{code: string, name: string, amount: mixed, is_taxable?: bool, is_active?: bool}>  $items
     * @return Collection<int, TModel>
     */
    private function replaceComponents(int $employeeId, array $items, string $modelClass, string $auditAction): Collection
    {
        $companyId = $this->companyContext->id();
        $employee = $this->requireEmployee($employeeId);

        return DB::transaction(function () use ($companyId, $employee, $items, $modelClass, $auditAction): Collection {
            $modelClass::query()
                ->where('company_id', $companyId)
                ->where('employee_id', $employee->id)
                ->delete();

            $created = collect();

            foreach ($items as $item) {
                $created->push($modelClass::query()->create([
                    'company_id' => $companyId,
                    'employee_id' => $employee->id,
                    'code' => $item['code'],
                    'name' => $item['name'],
                    'amount' => $item['amount'],
                    'is_taxable' => $item['is_taxable'] ?? true,
                    'is_active' => $item['is_active'] ?? true,
                ]));
            }

            $this->audit->write(
                action: $auditAction,
                subject: $employee,
                payload: ['employee_id' => $employee->id, 'count' => $created->count()],
            );

            return $created->values();
        });
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
