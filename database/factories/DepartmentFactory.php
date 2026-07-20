<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = Company::factory();

        return [
            'company_id' => $company,
            'branch_id' => Branch::factory()->for($company),
            'name' => fake()->words(2, true),
            'code' => strtoupper(fake()->unique()->bothify('DEPT-###')),
            'is_active' => true,
        ];
    }
}
