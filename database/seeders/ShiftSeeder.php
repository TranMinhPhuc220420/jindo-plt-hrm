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
                ],
            );
        }
    }
}
