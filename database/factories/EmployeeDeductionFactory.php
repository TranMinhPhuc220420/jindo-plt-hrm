<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDeduction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeDeduction>
 */
class EmployeeDeductionFactory extends Factory
{
    protected $model = EmployeeDeduction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'code' => 'INS',
            'name' => 'Insurance',
            'amount' => 800000,
            'is_taxable' => false,
            'is_active' => true,
        ];
    }
}
