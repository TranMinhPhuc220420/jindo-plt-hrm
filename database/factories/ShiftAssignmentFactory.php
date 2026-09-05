<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftAssignment>
 */
class ShiftAssignmentFactory extends Factory
{
    protected $model = ShiftAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = Company::factory();

        return [
            'company_id' => $company,
            'employee_id' => Employee::factory()->state([
                'company_id' => $company,
            ]),
            'shift_id' => Shift::factory()->state([
                'company_id' => $company,
            ]),
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'weekdays' => null,
        ];
    }
}
