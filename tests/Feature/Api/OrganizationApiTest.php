<?php

use App\Models\Company;
use App\Models\User;

function seedOrgAuth(): void
{
    seedAuthCatalog();
}

function orgManager(): User
{
    return actingUser([
        'can_view_organization',
        'can_manage_organization',
        'can_manage_company',
    ], prefix: 'org_mgr');
}

function viewerOnly(): User
{
    return actingUser(['can_view_organization'], prefix: 'org_view');
}

test('organization mutations require can_manage_organization', function () {
    Company::factory()->create(['code' => 'DEMO']);

    $viewer = viewerOnly();

    $this->actingAs($viewer)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/branches', [
            'name' => 'North',
            'code' => 'NORTH',
        ])
        ->assertForbidden()
        ->assertJsonPath('error_code', 'FORBIDDEN');
});

test('manager can create org hierarchy and fetch tree', function () {
    $company = Company::factory()->create(['code' => 'ACME', 'name' => 'Acme Corp']);
    $user = orgManager();

    $branch = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/branches', [
            'name' => 'Head Office',
            'code' => 'HQ',
        ]);

    $branch->assertCreated()
        ->assertJsonPath('data.code', 'HQ')
        ->assertJsonPath('data.company_id', $company->id);

    $branchId = $branch->json('data.id');

    $department = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/departments', [
            'branch_id' => $branchId,
            'name' => 'Engineering',
            'code' => 'ENG',
        ]);

    $department->assertCreated()->assertJsonPath('data.code', 'ENG');
    $departmentId = $department->json('data.id');

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/teams', [
            'department_id' => $departmentId,
            'name' => 'Platform',
            'code' => 'PLT',
        ])
        ->assertCreated();

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/positions', [
            'name' => 'Engineer',
            'code' => 'ENG-POS',
        ])
        ->assertCreated();

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/organization/tree')
        ->assertOk()
        ->assertJsonPath('data.company.code', 'ACME')
        ->assertJsonPath('data.branches.0.code', 'HQ')
        ->assertJsonPath('data.branches.0.departments.0.code', 'ENG')
        ->assertJsonPath('data.positions.0.code', 'ENG-POS');

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/companies/current')
        ->assertOk()
        ->assertJsonPath('data.code', 'ACME');
});

test('company update requires can_manage_company', function () {
    Company::factory()->create(['code' => 'CO1', 'name' => 'Old Name']);

    $viewer = viewerOnly();

    $this->actingAs($viewer)
        ->withHeaders(spaJsonHeaders())
        ->patchJson('/api/companies/current', [
            'name' => 'New Name',
        ])
        ->assertForbidden();

    $manager = orgManager();

    $this->actingAs($manager)
        ->withHeaders(spaJsonHeaders())
        ->patchJson('/api/companies/current', [
            'name' => 'New Name',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name');
});

test('branch create validates unique code and required fields', function () {
    Company::factory()->create(['code' => 'VAL']);
    $user = orgManager();

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/branches', [
            'name' => 'A',
            'code' => 'BR1',
        ])
        ->assertCreated();

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/branches', [
            'name' => 'B',
            'code' => 'BR1',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('error_code', 'VALIDATION_FAILED');

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/branches', [
            'name' => 'C',
        ])
        ->assertUnprocessable();
});

test('cannot delete branch that still has departments', function () {
    Company::factory()->create(['code' => 'DEL']);
    $user = orgManager();

    $branchId = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/branches', [
            'name' => 'HQ',
            'code' => 'HQ',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/departments', [
            'branch_id' => $branchId,
            'name' => 'Engineering',
            'code' => 'ENG',
        ])
        ->assertCreated();

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->deleteJson("/api/branches/{$branchId}")
        ->assertUnprocessable()
        ->assertJsonPath('error_code', 'BRANCH_HAS_DEPARTMENTS');
});

test('cannot delete department that still has teams', function () {
    Company::factory()->create(['code' => 'DEL2']);
    $user = orgManager();

    $branchId = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/branches', ['name' => 'HQ', 'code' => 'HQ2'])
        ->assertCreated()
        ->json('data.id');

    $departmentId = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/departments', [
            'branch_id' => $branchId,
            'name' => 'Engineering',
            'code' => 'ENG2',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/teams', [
            'department_id' => $departmentId,
            'name' => 'Backend',
            'code' => 'BE',
        ])
        ->assertCreated();

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->deleteJson("/api/departments/{$departmentId}")
        ->assertUnprocessable()
        ->assertJsonPath('error_code', 'DEPARTMENT_HAS_TEAMS');
});

test('manager can update branch', function () {
    Company::factory()->create(['code' => 'UPD']);
    $user = orgManager();

    $branchId = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/branches', [
            'name' => 'Old',
            'code' => 'OLD',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->patchJson("/api/branches/{$branchId}", [
            'name' => 'New Branch',
            'code' => 'NEW',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Branch')
        ->assertJsonPath('data.code', 'NEW');
});
