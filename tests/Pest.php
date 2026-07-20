<?php

use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit/Support');

pest()->extend(TestCase::class)
    ->in('Unit/Services');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Headers so Sanctum treats the request as a first-party SPA call in tests.
 *
 * @return array<string, string>
 */
function spaJsonHeaders(): array
{
    return [
        'Origin' => 'http://localhost',
        'Referer' => 'http://localhost',
    ];
}

/**
 * Seeds the permission/role catalog shared by every domain's auth helper.
 */
function seedAuthCatalog(): void
{
    test()->seed(PermissionSeeder::class);
    test()->seed(RoleSeeder::class);
}

/**
 * Builds a user with a throwaway role scoped to exactly the given permission
 * keys, optionally linking it to an existing Employee (for own-scope checks).
 *
 * @param  array<int, string>  $permissionKeys
 */
function actingUser(array $permissionKeys, ?Employee $employee = null, string $prefix = 'test'): User
{
    seedAuthCatalog();

    $user = User::factory()->create();
    $role = Role::factory()->create(['key' => $prefix.'_'.uniqid(), 'is_system' => false]);
    $ids = Permission::query()->whereIn('key', $permissionKeys)->pluck('id');
    $role->permissions()->sync($ids);
    $user->roles()->attach($role);

    if ($employee !== null) {
        $employee->update(['user_id' => $user->id]);
    }

    return $user->fresh('roles.permissions');
}
