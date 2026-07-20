<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeAllowance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeAllowance>
 */
class EmployeeAllowanceFactory extends Factory
{
    protected $model = EmployeeAllowance::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'code' => 'MEAL',
            'name' => 'Meal allowance',
            'amount' => 500000,
            'is_taxable' => true,
            'is_active' => true,
        ];
    }
}
