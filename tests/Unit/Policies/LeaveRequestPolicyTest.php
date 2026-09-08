<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Policies\LeaveRequestPolicy;

beforeEach(function () {
    seedAuthCatalog();
});

function makeLeaveRequestFor(Employee $employee, Company $company): LeaveRequest
{
    $type = LeaveType::factory()->create(['company_id' => $company->id]);

    return LeaveRequest::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'leave_type_id' => $type->id,
        'status' => 'pending',
    ]);
}

test('employee can view own leave request', function () {
    $company = Company::factory()->create();
    [$user, $employee] = actingUserInCompany($company, ['can_view_leave'], 'lv');
    $request = makeLeaveRequestFor($employee, $company);

    expect((new LeaveRequestPolicy)->view($user, $request))->toBeTrue();
});

test('peer without company-wide leave manage cannot view other employee leave', function () {
    $company = Company::factory()->create();
    [$peer] = actingUserInCompany($company, ['can_view_leave'], 'peer');
    $owner = Employee::factory()->create(['company_id' => $company->id]);
    $request = makeLeaveRequestFor($owner, $company);

    expect((new LeaveRequestPolicy)->view($peer, $request))->toBeFalse();
});

test('hr with leave balance manage can view any leave in company', function () {
    $company = Company::factory()->create();
    [$hr] = actingUserInCompany($company, ['can_view_leave', 'can_manage_leave_balances'], 'hr');
    $owner = Employee::factory()->create(['company_id' => $company->id]);
    $request = makeLeaveRequestFor($owner, $company);

    expect((new LeaveRequestPolicy)->view($hr, $request))->toBeTrue();
});

test('manager can approve direct report leave', function () {
    $company = Company::factory()->create();
    [$managerUser, $manager] = actingUserInCompany($company, ['can_approve_leave'], 'mgr');
    $report = Employee::factory()->create([
        'company_id' => $company->id,
        'manager_id' => $manager->id,
    ]);
    $request = makeLeaveRequestFor($report, $company);

    expect((new LeaveRequestPolicy)->approve($managerUser, $request))->toBeTrue();
});

test('manager cannot approve non-report leave', function () {
    $company = Company::factory()->create();
    [$managerUser] = actingUserInCompany($company, ['can_approve_leave'], 'mgr2');
    $stranger = Employee::factory()->create(['company_id' => $company->id]);
    $request = makeLeaveRequestFor($stranger, $company);

    expect((new LeaveRequestPolicy)->approve($managerUser, $request))->toBeFalse();
});

test('user without can_request_leave cannot create', function () {
    $user = actingUser(['can_view_leave'], prefix: 'noreq');

    expect((new LeaveRequestPolicy)->create($user))->toBeFalse();
});

test('user with can_request_leave can create', function () {
    $user = actingUser(['can_request_leave'], prefix: 'req');

    expect((new LeaveRequestPolicy)->create($user))->toBeTrue();
});
