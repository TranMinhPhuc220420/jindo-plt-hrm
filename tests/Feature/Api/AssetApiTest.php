<?php

use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;

function assetUser(array $permissionKeys): User
{
    return actingUser($permissionKeys, prefix: 'ast');
}

test('cannot create asset without can_manage_assets', function () {
    Company::factory()->create();
    $user = assetUser(['can_view_assets']);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/assets', [
            'code' => 'LAP-0001',
            'name' => 'MacBook Pro 14',
        ])
        ->assertForbidden();
});

test('creating an asset is audited', function () {
    Company::factory()->create();
    $user = assetUser(['can_view_assets', 'can_manage_assets']);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/assets', [
            'code' => 'LAP-0042',
            'name' => 'MacBook Pro 14',
            'category' => 'laptop',
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'available');

    expect(AuditLog::query()->where('action', 'asset.created')->count())->toBe(1);
});

test('assigning an asset is audited and marks it assigned', function () {
    $company = Company::factory()->create();
    $user = assetUser(['can_view_assets', 'can_assign_asset']);
    $asset = Asset::factory()->create(['company_id' => $company->id, 'status' => 'available']);
    $employee = Employee::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/assets/'.$asset->id.'/assign', [
            'employee_id' => $employee->id,
            'assigned_at' => '2026-07-16',
            'note' => 'Onboarding kit',
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'active');

    expect($asset->fresh()->status)->toBe('assigned');
    expect($asset->fresh()->assigned_to)->toBe($employee->id);
    expect(AuditLog::query()->where('action', 'asset.assigned')->count())->toBe(1);
});

test('cannot assign an already-assigned asset', function () {
    $company = Company::factory()->create();
    $user = assetUser(['can_assign_asset']);
    $asset = Asset::factory()->create(['company_id' => $company->id, 'status' => 'available']);
    $employee = Employee::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    $other = Employee::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/assets/'.$asset->id.'/assign', ['employee_id' => $employee->id])
        ->assertCreated();

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/assets/'.$asset->id.'/assign', ['employee_id' => $other->id])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'ASSET_ALREADY_ASSIGNED');
});

test('returning an asset is audited and frees inventory', function () {
    $company = Company::factory()->create();
    $user = assetUser(['can_assign_asset', 'can_return_asset']);
    $asset = Asset::factory()->create(['company_id' => $company->id, 'status' => 'available']);
    $employee = Employee::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/assets/'.$asset->id.'/assign', ['employee_id' => $employee->id])
        ->assertCreated();

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/assets/'.$asset->id.'/return', [
            'returned_at' => '2026-12-01',
            'condition' => 'good',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'returned');

    expect($asset->fresh()->status)->toBe('available');
    expect($asset->fresh()->assigned_to)->toBeNull();
    expect(AuditLog::query()->where('action', 'asset.returned')->count())->toBe(1);
});

test('cannot return an asset that is not assigned', function () {
    $company = Company::factory()->create();
    $user = assetUser(['can_return_asset']);
    $asset = Asset::factory()->create(['company_id' => $company->id, 'status' => 'available']);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/assets/'.$asset->id.'/return', ['condition' => 'good'])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'ASSET_NOT_AVAILABLE');
});

test('creating an asset with a duplicate code returns ASSET_CODE_DUPLICATE', function () {
    $company = Company::factory()->create();
    $user = assetUser(['can_manage_assets']);
    Asset::factory()->create(['company_id' => $company->id, 'code' => 'LAP-0099']);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/assets', ['code' => 'LAP-0099', 'name' => 'MacBook Air'])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'ASSET_CODE_DUPLICATE');
});

test('cannot retire an asset that is currently assigned', function () {
    $company = Company::factory()->create();
    $user = assetUser(['can_manage_assets', 'can_assign_asset']);
    $asset = Asset::factory()->create(['company_id' => $company->id, 'status' => 'available']);
    $employee = Employee::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/assets/'.$asset->id.'/assign', ['employee_id' => $employee->id])
        ->assertCreated();

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/assets/'.$asset->id.'/retire')
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'ASSET_INVALID_STATUS');
});
