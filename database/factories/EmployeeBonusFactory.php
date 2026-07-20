<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeBonus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeBonus>
 */
class EmployeeBonusFactory extends Factory
{
    protected $model = EmployeeBonus::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'code' => 'PERF',
            'name' => 'Performance bonus',
            'amount' => 1000000,
            'is_taxable' => true,
            'is_active' => true,
        ];
    }
}
