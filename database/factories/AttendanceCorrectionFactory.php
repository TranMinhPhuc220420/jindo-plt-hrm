<?php

namespace Database\Factories;

use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceCorrection>
 */
class AttendanceCorrectionFactory extends Factory
{
    protected $model = AttendanceCorrection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = Company::factory();
        $employee = Employee::factory()->state(['company_id' => $company]);
        $record = AttendanceRecord::factory()->state([
            'company_id' => $company,
            'employee_id' => $employee,
        ]);

        return [
            'company_id' => $company,
            'attendance_record_id' => $record,
            'employee_id' => $employee,
            'proposed_check_in_at' => now()->setTime(8, 0),
            'proposed_check_out_at' => now()->setTime(17, 0),
            'reason' => 'Forgot to check out',
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_note' => null,
        ];
    }
}
