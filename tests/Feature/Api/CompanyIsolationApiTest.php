<?php

/**
 * Cross-company isolation + permission-first regressions for primary API modules.
 *
 * @group critical
 */

use App\Models\Asset;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Document;
use App\Models\Employee;
use App\Models\JobOpening;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

test('leave list and show exclude foreign company leave requests', function () {
    $company = Company::factory()->create();
    $other = Company::factory()->create();
    [$user] = actingUserInCompany($company, ['can_view_leave', 'can_manage_leave_balances'], 'iso_lv');

    $ownType = LeaveType::factory()->create(['company_id' => $company->id]);
    $ownEmployee = Employee::factory()->create(['company_id' => $company->id]);
    $own = LeaveRequest::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $ownEmployee->id,
        'leave_type_id' => $ownType->id,
    ]);

    $foreignType = LeaveType::factory()->create(['company_id' => $other->id]);
    $foreignEmployee = Employee::factory()->create(['company_id' => $other->id]);
    $foreign = LeaveRequest::factory()->create([
        'company_id' => $other->id,
        'employee_id' => $foreignEmployee->id,
        'leave_type_id' => $foreignType->id,
    ]);

    $list = $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->getJson('/api/leave-requests')
        ->assertOk();

    $ids = collect($list->json('data'))->pluck('id');
    expect($ids)->toContain($own->id)->not->toContain($foreign->id);

    assertCannotAccessOtherCompany(
        $this->actingAs($user)->withHeaders(spaJsonHeaders())
            ->getJson("/api/leave-requests/{$foreign->id}")
    );
});

test('employee show for foreign company returns COMPANY_SCOPE_MISMATCH', function () {
    $company = Company::factory()->create();
    $other = Company::factory()->create();
    [$user] = actingUserInCompany($company, ['can_view_employee'], 'iso_emp');
    $foreign = Employee::factory()->create(['company_id' => $other->id]);

    assertCannotAccessOtherCompany(
        $this->actingAs($user)->withHeaders(spaJsonHeaders())
            ->getJson("/api/employees/{$foreign->id}")
    );
});

test('employee list does not include foreign company employees', function () {
    $company = Company::factory()->create();
    $other = Company::factory()->create();
    [$user, $self] = actingUserInCompany($company, ['can_view_employee'], 'iso_emp2');
    $foreign = Employee::factory()->create(['company_id' => $other->id, 'code' => 'FX-EMP-1']);

    $response = $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->getJson('/api/employees')
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($self->id)->not->toContain($foreign->id);
});

test('payroll run show for foreign company is denied', function () {
    $company = Company::factory()->create();
    $other = Company::factory()->create();
    [$user] = actingUserInCompany($company, ['can_view_payroll_history', 'can_run_payroll'], 'iso_pay');
    $foreign = PayrollRun::factory()->create(['company_id' => $other->id]);

    assertCannotAccessOtherCompany(
        $this->actingAs($user)->withHeaders(spaJsonHeaders())
            ->getJson("/api/payroll-runs/{$foreign->id}")
    );
});

test('payslip list excludes foreign company payslips', function () {
    $company = Company::factory()->create();
    $other = Company::factory()->create();
    [$user, $employee] = actingUserInCompany($company, ['can_view_payroll_history', 'can_manage_payslips'], 'iso_ps');

    $own = Payslip::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
    ]);
    $foreignEmployee = Employee::factory()->create(['company_id' => $other->id]);
    $foreign = Payslip::factory()->create([
        'company_id' => $other->id,
        'employee_id' => $foreignEmployee->id,
    ]);

    $response = $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->getJson('/api/payslips')
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($own->id)->not->toContain($foreign->id);
});

test('document show for foreign company is denied', function () {
    $company = Company::factory()->create();
    $other = Company::factory()->create();
    [$user] = actingUserInCompany($company, [
        'can_view_company_documents',
        'can_manage_company_documents',
    ], 'iso_doc');

    $foreign = Document::factory()->create([
        'company_id' => $other->id,
        'owner_type' => 'company',
    ]);

    assertCannotAccessOtherCompany(
        $this->actingAs($user)->withHeaders(spaJsonHeaders())
            ->getJson("/api/documents/{$foreign->id}")
    );
});

test('asset show for foreign company is denied', function () {
    $company = Company::factory()->create();
    $other = Company::factory()->create();
    [$user] = actingUserInCompany($company, ['can_view_assets', 'can_manage_assets'], 'iso_ast');
    $foreign = Asset::factory()->create(['company_id' => $other->id]);

    assertCannotAccessOtherCompany(
        $this->actingAs($user)->withHeaders(spaJsonHeaders())
            ->getJson("/api/assets/{$foreign->id}")
    );
});

test('job opening and candidate from foreign company are denied', function () {
    $company = Company::factory()->create();
    $other = Company::factory()->create();
    [$user] = actingUserInCompany($company, [
        'can_view_candidates',
        'can_manage_candidates',
        'can_manage_job_positions',
    ], 'iso_rec');

    $foreignOpening = JobOpening::factory()->create(['company_id' => $other->id]);
    $foreignCandidate = Candidate::factory()->create([
        'company_id' => $other->id,
        'job_opening_id' => $foreignOpening->id,
    ]);

    assertCannotAccessOtherCompany(
        $this->actingAs($user)->withHeaders(spaJsonHeaders())
            ->getJson("/api/job-openings/{$foreignOpening->id}")
    );

    assertCannotAccessOtherCompany(
        $this->actingAs($user)->withHeaders(spaJsonHeaders())
            ->getJson("/api/candidates/{$foreignCandidate->id}")
    );
});

test('employee report rows exclude foreign company employees', function () {
    $company = Company::factory()->create();
    $other = Company::factory()->create();
    [$user] = actingUserInCompany($company, ['can_view_employee_reports'], 'iso_rep');
    Employee::factory()->create(['company_id' => $company->id, 'status' => 'active', 'code' => 'REP-OWN']);
    $foreign = Employee::factory()->create(['company_id' => $other->id, 'status' => 'active', 'code' => 'REP-FX']);

    $response = $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->getJson('/api/reports/employees')
        ->assertOk();

    $codes = collect($response->json('data.rows'))->pluck('code');
    expect($codes)->toContain('REP-OWN')->not->toContain('REP-FX');
    expect($codes->contains($foreign->code))->toBeFalse();
});

test('permission-first: custom role with can_view_employee can list without HR role name', function () {
    seedAuthCatalog();
    Company::factory()->create();

    $user = User::factory()->create();
    $role = Role::factory()->create(['key' => 'custom_viewer_'.uniqid(), 'is_system' => false]);
    $permissionId = Permission::query()->where('key', 'can_view_employee')->value('id');
    $role->permissions()->sync([$permissionId]);
    $user->roles()->attach($role);

    $this->actingAs($user->fresh('roles.permissions'))
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/employees')
        ->assertOk();
});

test('permission-first: role named hr without can_create_employee cannot create employees', function () {
    seedAuthCatalog();
    Company::factory()->create();

    $user = User::factory()->create();
    $role = Role::factory()->create(['key' => 'hr_'.uniqid(), 'name' => 'HR', 'is_system' => false]);
    $viewId = Permission::query()->where('key', 'can_view_employee')->value('id');
    $role->permissions()->sync([$viewId]);
    $user->roles()->attach($role);

    $this->actingAs($user->fresh('roles.permissions'))
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/employees', [
            'first_name' => 'A',
            'last_name' => 'B',
            'code' => 'NO-PERM-1',
            'status' => 'active',
        ])
        ->assertForbidden();
});
