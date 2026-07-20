<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Position;
use App\Models\Team;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $company = Company::query()->updateOrCreate(
            ['code' => 'JINDO'],
            [
                'name' => 'Jindo Demo Company',
                'legal_name' => 'Jindo Demo Company LLC',
                'email' => 'hr@example.test',
                'phone' => '+84 28 0000 0000',
                'address' => 'Ho Chi Minh City, Vietnam',
                'is_active' => true,
            ],
        );

        $branch = Branch::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => 'HQ'],
            [
                'name' => 'Head Office',
                'address' => 'Ho Chi Minh City',
                'is_active' => true,
            ],
        );

        $engineering = Department::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => 'ENG'],
            [
                'branch_id' => $branch->id,
                'name' => 'Engineering',
                'is_active' => true,
            ],
        );

        Department::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => 'HR'],
            [
                'branch_id' => $branch->id,
                'name' => 'Human Resources',
                'is_active' => true,
            ],
        );

        Team::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => 'ENG-CORE'],
            [
                'department_id' => $engineering->id,
                'name' => 'Core Platform',
                'is_active' => true,
            ],
        );

        Position::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => 'SE'],
            [
                'name' => 'Software Engineer',
                'description' => 'Builds and maintains product features',
                'is_active' => true,
            ],
        );

        Position::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => 'HRBP'],
            [
                'name' => 'HR Business Partner',
                'description' => 'Supports people operations',
                'is_active' => true,
            ],
        );

        app(SettingsService::class)->seedDefaultsForCompany($company->id);
    }
}
