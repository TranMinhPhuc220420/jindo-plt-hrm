<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->call(ProductionBootstrapSeeder::class);

            return;
        }

        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            CompanySeeder::class,
        ]);

        // Create admin before EmployeeSeeder so the manager employee can link user_id.
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.test'],
            [
                'name' => 'Admin User',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $adminRole = Role::query()->where('key', 'admin')->firstOrFail();
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);

        $this->call([
            EmployeeSeeder::class,
            ShiftSeeder::class,
            AttendanceSeeder::class,
            LeaveSeeder::class,
            PayrollSeeder::class,
            DocumentSeeder::class,
            AssetSeeder::class,
            RecruitmentSeeder::class,
            OnboardingSeeder::class,
            NotificationSeeder::class,
            PerformanceSeeder::class,
        ]);
    }
}
