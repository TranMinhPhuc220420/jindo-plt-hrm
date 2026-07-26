<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\OvertimeRule;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use Database\Seeders\ProductionBootstrapSeeder;
use Illuminate\Support\Facades\Hash;

test('production bootstrap seeder requires admin credentials', function () {
    config([
        'hrm.seed.admin_email' => null,
        'hrm.seed.admin_password' => null,
    ]);

    expect(fn () => $this->seed(ProductionBootstrapSeeder::class))
        ->toThrow(RuntimeException::class, 'SEED_ADMIN_EMAIL and SEED_ADMIN_PASSWORD');
});

test('production bootstrap seeder creates company settings shifts and admin', function () {
    config([
        'hrm.seed.company_code' => 'ACME',
        'hrm.seed.company_name' => 'Acme Corp',
        'hrm.seed.admin_email' => 'admin@acme.test',
        'hrm.seed.admin_password' => 'secure-pass-123',
    ]);

    $this->seed(ProductionBootstrapSeeder::class);

    $company = Company::query()->where('code', 'ACME')->first();
    expect($company)->not->toBeNull()
        ->and($company->name)->toBe('Acme Corp')
        ->and($company->is_active)->toBeTrue();

    expect(Setting::query()->where('company_id', $company->id)->count())->toBeGreaterThan(0);

    expect(Shift::query()->where('company_id', $company->id)->where('code', 'MORNING')->exists())->toBeTrue();
    expect(Shift::query()->where('company_id', $company->id)->where('code', 'NIGHT')->exists())->toBeTrue();
    expect(OvertimeRule::query()->where('company_id', $company->id)->where('code', 'STANDARD')->exists())->toBeTrue();

    expect(ShiftAssignment::query()->count())->toBe(0);
    expect(Employee::query()->count())->toBe(0);

    $admin = User::query()->where('email', 'admin@acme.test')->first();
    expect($admin)->not->toBeNull()
        ->and(Hash::check('secure-pass-123', $admin->password))->toBeTrue();

    $adminRole = Role::query()->where('key', 'admin')->firstOrFail();
    expect($admin->roles()->where('roles.id', $adminRole->id)->exists())->toBeTrue();

    // Idempotent re-run
    $this->seed(ProductionBootstrapSeeder::class);
    expect(Company::query()->where('code', 'ACME')->count())->toBe(1);
    expect(User::query()->where('email', 'admin@acme.test')->count())->toBe(1);
    expect(Shift::query()->where('company_id', $company->id)->count())->toBe(2);
});
