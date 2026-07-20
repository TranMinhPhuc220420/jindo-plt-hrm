<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'unit' => 'day',
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-10',
            'is_half_day' => false,
            'quantity' => 1,
            'reason' => 'Personal',
            'status' => 'pending',
        ];
    }
}
