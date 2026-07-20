<?php

use App\Models\Role;
use App\Models\User;
use App\Support\PermissionCatalog;

function seedAuthorization(): void
{
    seedAuthCatalog();
}

function userWithPermissions(array $permissionKeys): User
{
    return actingUser($permissionKeys, prefix: 'custom');
}

test('me returns permission keys for assigned roles', function () {
    seedAuthorization();

    $user = User::factory()->create();
    $admin = Role::query()->where('key', 'admin')->firstOrFail();
    $user->roles()->attach($admin);

    $this->withHeaders(spaJsonHeaders())
        ->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertOk();

    $this->withHeaders(spaJsonHeaders())
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('success', true);

    $permissions = $this->withHeaders(spaJsonHeaders())
        ->getJson('/api/me')
        ->json('data.permissions');

    expect($permissions)->toEqualCanonicalizing(PermissionCatalog::allKeys());
});

test('roles index requires can_view_roles', function () {
    $denied = userWithPermissions([]);

    $this->actingAs($denied)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/roles')
        ->assertForbidden()
        ->assertJsonPath('error_code', 'FORBIDDEN');

    $allowed = userWithPermissions(['can_view_roles']);

    $this->actingAs($allowed)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/roles')
        ->assertOk()
        ->assertJsonPath('success', true);
});

test('cannot manage roles without can_manage_roles', function () {
    $user = userWithPermissions(['can_view_roles']);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/roles', [
            'key' => 'contractors',
            'name' => 'Contractors',
        ])
        ->assertForbidden();
});

test('admin can create role and sync permissions', function () {
    $user = userWithPermissions([
        'can_view_roles',
        'can_manage_roles',
    ]);

    $create = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/roles', [
            'key' => 'contractors',
            'name' => 'Contractors',
            'description' => 'External staff',
        ]);

    $create->assertCreated()
        ->assertJsonPath('data.key', 'contractors');

    $roleId = $create->json('data.id');

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->putJson("/api/roles/{$roleId}/permissions", [
            'permissions' => ['can_view_organization'],
        ])
        ->assertOk()
        ->assertJsonPath('data.permissions.0.key', 'can_view_organization');
});

test('can assign roles to users with can_assign_roles', function () {
    seedAuthorization();

    $actor = userWithPermissions(['can_assign_roles', 'can_view_roles']);
    $subject = User::factory()->create();

    $this->actingAs($actor)
        ->withHeaders(spaJsonHeaders())
        ->putJson("/api/users/{$subject->id}/roles", [
            'roles' => ['manager'],
        ])
        ->assertOk()
        ->assertJsonPath('data.0.key', 'manager');

    expect($subject->fresh()->hasPermission('can_view_organization'))->toBeTrue();
});

test('syncing an unknown permission key returns PERMISSION_KEYS_INVALID', function () {
    $user = userWithPermissions(['can_view_roles', 'can_manage_roles']);

    $roleId = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/roles', ['key' => 'contractors2', 'name' => 'Contractors 2'])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->putJson("/api/roles/{$roleId}/permissions", [
            'permissions' => ['can_view_organization', 'not_a_real_permission'],
        ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'PERMISSION_KEYS_INVALID');
});

test('assigning an unknown role key returns ROLE_KEYS_INVALID', function () {
    seedAuthorization();

    $actor = userWithPermissions(['can_assign_roles', 'can_view_roles']);
    $subject = User::factory()->create();

    $this->actingAs($actor)
        ->withHeaders(spaJsonHeaders())
        ->putJson("/api/users/{$subject->id}/roles", [
            'roles' => ['not_a_real_role'],
        ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'ROLE_KEYS_INVALID');
});

test('a system role cannot be deleted', function () {
    seedAuthorization();

    $user = userWithPermissions(['can_view_roles', 'can_manage_roles']);
    $admin = Role::query()->where('key', 'admin')->firstOrFail();

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->deleteJson("/api/roles/{$admin->id}")
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'ROLE_SYSTEM_PROTECTED');
});

test('authorization never checks role display names', function () {
    seedAuthorization();

    $user = User::factory()->create();
    $hr = Role::query()->where('key', 'hr')->firstOrFail();
    $user->roles()->attach($hr);

    // HR has can_view_roles but not can_manage_roles / can_assign_roles
    expect($user->can('can_view_roles'))->toBeTrue()
        ->and($user->can('can_manage_roles'))->toBeFalse()
        ->and($user->hasPermission('can_view_roles'))->toBeTrue();
});
