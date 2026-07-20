<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
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

        $branch = Branch::query()->where('company_id', $company->id)->where('code', 'HQ')->first();
        $engineering = Department::query()->where('company_id', $company->id)->where('code', 'ENG')->first();
        $team = Team::query()->where('company_id', $company->id)->where('code', 'ENG-CORE')->first();
        $engineer = Position::query()->where('company_id', $company->id)->where('code', 'SE')->first();
        $hrbp = Position::query()->where('company_id', $company->id)->where('code', 'HRBP')->first();

        $manager = Employee::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => 'E-0001'],
            [
                'first_name' => 'Alex',
                'last_name' => 'Nguyen',
                'full_name' => 'Alex Nguyen',
                'email' => 'alex.nguyen@example.test',
                'phone' => '+84 90 111 0001',
                'branch_id' => $branch?->id,
                'department_id' => $engineering?->id,
                'team_id' => $team?->id,
                'position_id' => $engineer?->id,
                'hired_at' => '2024-01-15',
                'status' => 'active',
            ],
        );

        Employee::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => 'E-0002'],
            [
                'first_name' => 'Binh',
                'last_name' => 'Tran',
                'full_name' => 'Binh Tran',
                'email' => 'binh.tran@example.test',
                'phone' => '+84 90 111 0002',
                'branch_id' => $branch?->id,
                'department_id' => $engineering?->id,
                'team_id' => $team?->id,
                'position_id' => $engineer?->id,
                'manager_id' => $manager->id,
                'hired_at' => '2025-03-01',
                'status' => 'probation',
            ],
        );

        Employee::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => 'E-0003'],
            [
                'first_name' => 'Chi',
                'last_name' => 'Le',
                'full_name' => 'Chi Le',
                'email' => 'chi.le@example.test',
                'phone' => '+84 90 111 0003',
                'branch_id' => $branch?->id,
                'department_id' => Department::query()
                    ->where('company_id', $company->id)
                    ->where('code', 'HR')
                    ->value('id'),
                'position_id' => $hrbp?->id,
                'hired_at' => '2023-06-10',
                'status' => 'active',
            ],
        );

        $admin = User::query()->where('email', 'admin@example.test')->first();

        if ($admin) {
            $manager->forceFill(['user_id' => $admin->id])->save();
        }
    }
}
