<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $company = Company::query()->where('code', 'JINDO')->first();

        if ($company === null) {
            return;
        }

        $employee = Employee::query()
            ->where('company_id', $company->id)
            ->where('code', 'E-0001')
            ->first();

        if ($employee === null) {
            return;
        }

        $tz = config('app.timezone');
        $day1 = CarbonImmutable::now($tz)->startOfMonth()->addDays(1);
        $day2 = $day1->addDay();

        AttendanceRecord::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'work_date' => $day1->toDateString(),
            ],
            [
                'check_in_at' => $day1->setTime(8, 5),
                'check_out_at' => $day1->setTime(17, 10),
                'worked_minutes' => 485,
                'late_minutes' => 5,
                'early_leave_minutes' => 0,
                'overtime_minutes' => 10,
                'break_minutes' => 60,
                'status' => 'pending',
                'source' => 'manual',
            ],
        );

        AttendanceRecord::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'work_date' => $day2->toDateString(),
            ],
            [
                'check_in_at' => $day2->setTime(8, 0),
                'check_out_at' => $day2->setTime(17, 0),
                'worked_minutes' => 480,
                'late_minutes' => 0,
                'early_leave_minutes' => 0,
                'overtime_minutes' => 0,
                'break_minutes' => 60,
                'status' => 'approved',
                'source' => 'manual',
            ],
        );
    }
}
