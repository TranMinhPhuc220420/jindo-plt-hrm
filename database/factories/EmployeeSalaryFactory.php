<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeSalary>
 */
class EmployeeSalaryFactory extends Factory
{
    protected $model = EmployeeSalary::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'amount' => 15000000,
            'currency' => 'VND',
            'strategy' => 'monthly',
            'effective_from' => '2026-01-01',
            'effective_to' => null,
        ];
    }
}
