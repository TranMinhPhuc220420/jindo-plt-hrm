<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\Payslip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payslip>
 */
class PayslipFactory extends Factory
{
    protected $model = Payslip::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'payroll_run_id' => PayrollRun::factory(),
            'payroll_item_id' => PayrollItem::factory(),
            'employee_id' => Employee::factory(),
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'gross' => 16000000,
            'net' => 14200000,
            'components' => [
                ['type' => 'salary', 'label' => 'Base', 'amount' => '15000000.00'],
            ],
            'pdf_path' => null,
        ];
    }
}
