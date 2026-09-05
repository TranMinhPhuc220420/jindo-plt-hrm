<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use Database\Seeders\Concerns\SeedsShiftDefinitions;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    use SeedsShiftDefinitions;

    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $company = Company::query()->where('code', 'JINDO')->first();

        if ($company === null) {
            return;
        }

        $this->seedShiftDefinitions($company->id);

        $morning = Shift::query()
            ->where('company_id', $company->id)
            ->where('code', 'MORNING')
            ->first();

        $employee = Employee::query()
            ->where('company_id', $company->id)
            ->where('code', 'E-0001')
            ->first();

        if ($morning !== null && $employee !== null) {
            ShiftAssignment::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'shift_id' => $morning->id,
                    'start_date' => now()->startOfMonth()->toDateString(),
                ],
                [
                    'end_date' => now()->endOfMonth()->toDateString(),
                    'weekdays' => null,
                ],
            );
        }

        $morningPt = Shift::query()
            ->where('company_id', $company->id)
            ->where('code', 'MORNING_PT')
            ->first();
        $afternoonPt = Shift::query()
            ->where('company_id', $company->id)
            ->where('code', 'AFTERNOON_PT')
            ->first();
        $partTime = Employee::query()
            ->where('company_id', $company->id)
            ->where('code', 'E-0002')
            ->first();

        if ($morningPt !== null && $afternoonPt !== null && $partTime !== null) {
            ShiftAssignment::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $partTime->id,
                    'shift_id' => $morningPt->id,
                    'start_date' => now()->startOfMonth()->toDateString(),
                ],
                [
                    'end_date' => now()->endOfMonth()->toDateString(),
                    'weekdays' => [1, 3, 5],
                ],
            );
            ShiftAssignment::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $partTime->id,
                    'shift_id' => $afternoonPt->id,
                    'start_date' => now()->startOfMonth()->toDateString(),
                ],
                [
                    'end_date' => now()->endOfMonth()->toDateString(),
                    'weekdays' => [1, 3, 5],
                ],
            );
        }
    }
}
