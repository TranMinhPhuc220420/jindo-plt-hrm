<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\WeekendRule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class LeaveSeeder extends Seeder
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

        WeekendRule::query()->updateOrCreate(
            ['company_id' => $company->id],
            ['weekend_days' => [0, 6]],
        );

        $types = [
            ['code' => 'ANNUAL', 'name' => 'Annual Leave', 'is_paid' => true, 'requires_balance' => true],
            ['code' => 'SICK', 'name' => 'Sick Leave', 'is_paid' => true, 'requires_balance' => true],
            ['code' => 'UNPAID', 'name' => 'Unpaid Leave', 'is_paid' => false, 'requires_balance' => false],
            ['code' => 'COMP', 'name' => 'Compensation Leave', 'is_paid' => true, 'requires_balance' => true],
        ];

        $annual = null;

        foreach ($types as $row) {
            $type = LeaveType::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => $row['code'],
                ],
                [
                    'name' => $row['name'],
                    'unit_default' => 'day',
                    'is_paid' => $row['is_paid'],
                    'requires_balance' => $row['requires_balance'],
                    'allows_negative' => false,
                    'is_active' => true,
                ],
            );

            if ($row['code'] === 'ANNUAL') {
                $annual = $type;
            }
        }

        $year = (string) now()->year;

        Holiday::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'date' => $year.'-01-01',
            ],
            ['name' => 'New Year'],
        );

        Holiday::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'date' => $year.'-09-02',
            ],
            ['name' => 'National Day'],
        );

        if ($annual === null) {
            return;
        }

        LeaveBalance::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'leave_type_id' => $annual->id,
                'period_key' => $year,
            ],
            [
                'company_id' => $company->id,
                'entitled' => 12,
                'used' => 1,
                'pending' => 1,
            ],
        );

        $approvedStart = CarbonImmutable::now()->startOfMonth()->next(CarbonImmutable::WEDNESDAY);
        $pendingStart = $approvedStart->addWeeks(2);

        LeaveRequest::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $annual->id,
                'start_date' => $approvedStart->toDateString(),
                'end_date' => $approvedStart->toDateString(),
                'status' => 'approved',
            ],
            [
                'unit' => 'day',
                'is_half_day' => false,
                'quantity' => 1,
                'reason' => 'Seeded approved leave',
            ],
        );

        LeaveRequest::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $annual->id,
                'start_date' => $pendingStart->toDateString(),
                'end_date' => $pendingStart->toDateString(),
                'status' => 'pending',
            ],
            [
                'unit' => 'day',
                'is_half_day' => false,
                'quantity' => 1,
                'reason' => 'Seeded pending leave',
            ],
        );
    }
}
