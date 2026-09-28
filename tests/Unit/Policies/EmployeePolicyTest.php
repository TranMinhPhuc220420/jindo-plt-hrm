<?php

use App\Models\Company;
use App\Models\Employee;
use App\Policies\EmployeePolicy;

beforeEach(function () {
    seedAuthCatalog();
});

test('viewAny and view require can_view_employee', function () {
    $company = Company::factory()->create();
    $employee = Employee::factory()->create(['company_id' => $company->id]);
    $allowed = actingUser(['can_view_employee'], prefix: 'ev');
    $denied = actingUser([], prefix: 'eno');

    $policy = new EmployeePolicy;

    expect($policy->viewAny($allowed))->toBeTrue()
        ->and($policy->view($allowed, $employee))->toBeTrue()
        ->and($policy->viewAny($denied))->toBeFalse()
        ->and($policy->view($denied, $employee))->toBeFalse();
});

test('create requires can_create_employee', function () {
    expect((new EmployeePolicy)->create(actingUser(['can_view_employee'], prefix: 'ec')))->toBeFalse()
        ->and((new EmployeePolicy)->create(actingUser(['can_create_employee'], prefix: 'ec2')))->toBeTrue();
});

test('update and changeStatus require dedicated permissions', function () {
    $company = Company::factory()->create();
    $employee = Employee::factory()->create(['company_id' => $company->id]);
    $updater = actingUser(['can_update_employee'], prefix: 'eu');
    $statusChanger = actingUser(['can_change_employee_status'], prefix: 'es');

    $policy = new EmployeePolicy;

    expect($policy->update($updater, $employee))->toBeTrue()
        ->and($policy->changeStatus($updater, $employee))->toBeFalse()
        ->and($policy->changeStatus($statusChanger, $employee))->toBeTrue()
        ->and($policy->update($statusChanger, $employee))->toBeFalse();
});

test('employee can update own avatar without can_update_employee', function () {
    $company = Company::factory()->create();
    [$user, $employee] = actingUserInCompany($company, [], 'av');

    expect((new EmployeePolicy)->updateAvatar($user, $employee))->toBeTrue();
});

test('peer cannot update another employee avatar without can_update_employee', function () {
    $company = Company::factory()->create();
    [$peer] = actingUserInCompany($company, [], 'av2');
    $other = Employee::factory()->create(['company_id' => $company->id]);

    expect((new EmployeePolicy)->updateAvatar($peer, $other))->toBeFalse();
});

test('hr with can_update_employee can update any avatar', function () {
    $company = Company::factory()->create();
    [$hr] = actingUserInCompany($company, ['can_update_employee'], 'avhr');
    $other = Employee::factory()->create(['company_id' => $company->id]);

    expect((new EmployeePolicy)->updateAvatar($hr, $other))->toBeTrue();
});
