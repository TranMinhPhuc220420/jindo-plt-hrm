<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\OvertimeRule;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
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

        $morning = Shift::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => 'MORNING'],
            [
                'name' => 'Morning',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'break_minutes' => 60,
                'kind' => 'standard',
                'is_night' => false,
                'is_flexible' => false,
                'is_active' => true,
            ],
        );

        Shift::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => 'NIGHT'],
            [
                'name' => 'Night',
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
                'break_minutes' => 45,
                'kind' => 'night',
                'is_night' => true,
                'is_flexible' => false,
                'is_active' => true,
            ],
        );

        OvertimeRule::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => 'STANDARD'],
            [
                'name' => 'Standard overtime',
                'applies_after_minutes' => 0,
                'allow_before_shift' => false,
                'night_ot_enabled' => true,
                'is_active' => true,
            ],
        );

        $employee = Employee::query()
            ->where('company_id', $company->id)
            ->where('code', 'E-0001')
            ->first();

        if ($employee !== null) {
            ShiftAssignment::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'shift_id' => $morning->id,
                    'start_date' => now()->startOfMonth()->toDateString(),
                ],
                [
                    'end_date' => now()->endOfMonth()->toDateString(),
                ],
            );
        }
    }
}
