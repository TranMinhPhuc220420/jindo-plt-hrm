<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeAllowance;
use App\Models\EmployeeDeduction;
use App\Models\EmployeeSalary;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\Payslip;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class PayrollSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $company = Company::query()->where('code', 'JINDO')->first();

        if ($company === null) {
            return;
        }

        $employee = Employee::query()
            ->where('company_id', $company->id)
            ->where('code', 'E-0001')
            ->first();

        if ($employee === null) {
            return;
        }

        $effectiveFrom = CarbonImmutable::now()->startOfYear()->toDateString();

        EmployeeSalary::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'effective_from' => $effectiveFrom,
            ],
            [
                'amount' => 15000000,
                'currency' => 'VND',
                'strategy' => 'monthly',
                'effective_to' => null,
            ],
        );

        EmployeeAllowance::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'code' => 'MEAL',
            ],
            [
                'name' => 'Meal allowance',
                'amount' => 500000,
                'is_taxable' => false,
                'is_active' => true,
            ],
        );

        EmployeeDeduction::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'code' => 'SI',
            ],
            [
                'name' => 'Social insurance',
                'amount' => 1200000,
                'is_taxable' => false,
                'is_active' => true,
            ],
        );

        $periodStart = CarbonImmutable::now()->startOfMonth()->toDateString();
        $periodEnd = CarbonImmutable::now()->endOfMonth()->toDateString();

        $run = PayrollRun::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'run_type' => 'regular',
            ],
            [
                'name' => 'Demo payroll '.$periodStart,
                'status' => 'finalized',
                'employee_count' => 1,
                'total_gross' => 15500000,
                'total_net' => 14300000,
                'calculated_at' => now(),
                'approved_at' => now(),
                'finalized_at' => now(),
            ],
        );

        $components = [
            ['code' => 'BASE', 'name' => 'Base salary', 'amount' => 15000000, 'type' => 'earning'],
            ['code' => 'MEAL', 'name' => 'Meal allowance', 'amount' => 500000, 'type' => 'earning'],
            ['code' => 'SI', 'name' => 'Social insurance', 'amount' => -1200000, 'type' => 'deduction'],
        ];

        $item = PayrollItem::query()->updateOrCreate(
            [
                'payroll_run_id' => $run->id,
                'employee_id' => $employee->id,
            ],
            [
                'company_id' => $company->id,
                'gross' => 15500000,
                'net' => 14300000,
                'components' => $components,
            ],
        );

        Payslip::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'payroll_run_id' => $run->id,
                'employee_id' => $employee->id,
            ],
            [
                'payroll_item_id' => $item->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'gross' => 15500000,
                'net' => 14300000,
                'components' => $components,
                'pdf_path' => null,
            ],
        );
    }
}
