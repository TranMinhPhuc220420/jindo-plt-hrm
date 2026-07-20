<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = Company::factory();

        return [
            'company_id' => $company,
            'employee_id' => Employee::factory()->state(['company_id' => $company]),
            'work_date' => now()->toDateString(),
            'check_in_at' => now()->setTime(8, 0),
            'check_out_at' => now()->setTime(17, 0),
            'worked_minutes' => 480,
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'overtime_minutes' => 0,
            'break_minutes' => 60,
            'status' => 'pending',
            'source' => 'manual',
            'note' => null,
            'approved_by' => null,
            'approved_at' => null,
        ];
    }
}
