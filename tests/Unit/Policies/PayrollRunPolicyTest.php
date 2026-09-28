<?php

use App\Models\Company;
use App\Models\PayrollRun;
use App\Policies\PayrollRunPolicy;

beforeEach(function () {
    seedAuthCatalog();
});

test('user with can_run_payroll can create and calculate', function () {
    $user = actingUser(['can_run_payroll'], prefix: 'pr');
    $run = PayrollRun::factory()->create();

    $policy = new PayrollRunPolicy;

    expect($policy->create($user))->toBeTrue()
        ->and($policy->calculate($user, $run))->toBeTrue()
        ->and($policy->view($user, $run))->toBeTrue();
});

test('user with only view history cannot approve or create', function () {
    $user = actingUser(['can_view_payroll_history'], prefix: 'pv');
    $run = PayrollRun::factory()->create();

    $policy = new PayrollRunPolicy;

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->create($user))->toBeFalse()
        ->and($policy->approve($user, $run))->toBeFalse();
});

test('user with can_approve_payroll can approve and finalize', function () {
    $user = actingUser(['can_approve_payroll'], prefix: 'pa');
    $run = PayrollRun::factory()->create();

    $policy = new PayrollRunPolicy;

    expect($policy->approve($user, $run))->toBeTrue()
        ->and($policy->finalize($user, $run))->toBeTrue()
        ->and($policy->create($user))->toBeFalse();
});

test('user without payroll permissions is denied viewAny', function () {
    $company = Company::factory()->create();
    [$user] = actingUserInCompany($company, ['can_view_employee'], 'nopay');

    expect((new PayrollRunPolicy)->viewAny($user))->toBeFalse();
});

test('permission-first: custom role with can_run_payroll is allowed without HR role name', function () {
    $user = actingUser(['can_run_payroll'], prefix: 'custom_pay');
    $run = PayrollRun::factory()->create();

    expect((new PayrollRunPolicy)->create($user))->toBeTrue()
        ->and((new PayrollRunPolicy)->update($user, $run))->toBeTrue();
});
