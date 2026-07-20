<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollItem>
 */
class PayrollItemFactory extends Factory
{
    protected $model = PayrollItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payroll_run_id' => PayrollRun::factory(),
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'gross' => 16000000,
            'net' => 14200000,
            'components' => [
                ['type' => 'salary', 'label' => 'Base', 'amount' => '15000000.00'],
            ],
        ];
    }
}
