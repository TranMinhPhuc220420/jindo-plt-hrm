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
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            CompanySeeder::class,
            EmployeeSeeder::class,
            ShiftSeeder::class,
            AttendanceSeeder::class,
            LeaveSeeder::class,
            PayrollSeeder::class,
            DocumentSeeder::class,
            AssetSeeder::class,
            RecruitmentSeeder::class,
            OnboardingSeeder::class,
        ]);

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

        // After admin user exists so inbox samples can attach.
        $this->call([
            NotificationSeeder::class,
            PerformanceSeeder::class,
        ]);
    }
}
