<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Database\Seeders\Concerns\SeedsShiftDefinitions;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Curated production bootstrap: permissions, roles, company, settings, shifts, admin.
 * Safe to call from staging for smoke tests. Idempotent.
 *
 * Requires SEED_ADMIN_EMAIL and SEED_ADMIN_PASSWORD.
 * Company uses SEED_COMPANY_CODE / SEED_COMPANY_NAME (defaults: JINDO / Jindo).
 */
class ProductionBootstrapSeeder extends Seeder
{
    use SeedsShiftDefinitions;

    public function run(): void
    {
        $adminEmail = config('hrm.seed.admin_email');
        $adminPassword = config('hrm.seed.admin_password');

        if (! is_string($adminEmail) || $adminEmail === '' || ! is_string($adminPassword) || $adminPassword === '') {
            throw new RuntimeException(
                'ProductionBootstrapSeeder requires SEED_ADMIN_EMAIL and SEED_ADMIN_PASSWORD to be set.',
            );
        }

        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);

        $companyCode = (string) config('hrm.seed.company_code', 'JINDO');
        $companyName = (string) config('hrm.seed.company_name', 'Jindo');

        $company = Company::query()->updateOrCreate(
            ['code' => $companyCode],
            [
                'name' => $companyName,
                'is_active' => true,
            ],
        );

        app(SettingsService::class)->seedDefaultsForCompany($company->id);

        $this->seedShiftDefinitions($company->id);

        $admin = User::query()->updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Admin',
                'password' => $adminPassword,
                'email_verified_at' => now(),
            ],
        );

        $adminRole = Role::query()->where('key', 'admin')->firstOrFail();
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);
    }
}
