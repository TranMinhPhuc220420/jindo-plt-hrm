<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Deterministic dataset for Playwright / CI browser smoke.
 * Credentials:
 * - admin@example.test / password (full admin, linked to manager employee)
 * - viewer@example.test / password (no employee permissions — UI gates)
 */
class E2eSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $this->call(DatabaseSeeder::class);

        $viewer = User::query()->updateOrCreate(
            ['email' => 'viewer@example.test'],
            [
                'name' => 'Limited Viewer',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $role = Role::query()->firstOrCreate(
            ['key' => 'e2e_viewer'],
            [
                'name' => 'E2E Viewer',
                'description' => 'Playwright limited user',
                'is_system' => false,
            ],
        );

        // Intentionally no can_view_employee / can_create_employee.
        $permissionIds = Permission::query()
            ->whereIn('key', ['can_view_leave', 'can_request_leave'])
            ->pluck('id');
        $role->permissions()->sync($permissionIds);
        $viewer->roles()->sync([$role->id]);

        $companyId = Employee::query()->value('company_id');
        if ($companyId !== null && $viewer->employee === null) {
            Employee::factory()->create([
                'company_id' => $companyId,
                'user_id' => $viewer->id,
                'code' => 'E-E2E-VIEWER',
                'first_name' => 'Limited',
                'last_name' => 'Viewer',
                'full_name' => 'Limited Viewer',
                'email' => 'viewer.employee@example.test',
                'status' => 'active',
            ]);
        }
    }
}
