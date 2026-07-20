<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $first = fake()->firstName();
        $last = fake()->lastName();

        return [
            'company_id' => Company::factory(),
            'code' => 'E-'.fake()->unique()->numerify('####'),
            'first_name' => $first,
            'last_name' => $last,
            'full_name' => trim($first.' '.$last),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'branch_id' => null,
            'department_id' => null,
            'team_id' => null,
            'position_id' => null,
            'manager_id' => null,
            'supervisor_id' => null,
            'hr_owner_id' => null,
            'user_id' => null,
            'hired_at' => fake()->date(),
            'status' => 'active',
        ];
    }
}
