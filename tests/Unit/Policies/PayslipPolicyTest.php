<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\Payslip;
use App\Policies\PayslipPolicy;

beforeEach(function () {
    seedAuthCatalog();
});

test('employee with can_view_salary can view own payslip only', function () {
    $company = Company::factory()->create();
    [$user, $employee] = actingUserInCompany($company, ['can_view_salary'], 'ps');
    $own = Payslip::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
    ]);
    $otherEmployee = Employee::factory()->create(['company_id' => $company->id]);
    $other = Payslip::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $otherEmployee->id,
    ]);

    $policy = new PayslipPolicy;

    expect($policy->view($user, $own))->toBeTrue()
        ->and($policy->view($user, $other))->toBeFalse()
        ->and($policy->download($user, $own))->toBeTrue();
});

test('payroll history permission can view any payslip', function () {
    $company = Company::factory()->create();
    [$hr] = actingUserInCompany($company, ['can_view_payroll_history'], 'pshr');
    $employee = Employee::factory()->create(['company_id' => $company->id]);
    $payslip = Payslip::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
    ]);

    expect((new PayslipPolicy)->view($hr, $payslip))->toBeTrue();
});

test('user without salary permissions is denied viewAny', function () {
    $user = actingUser(['can_view_employee'], prefix: 'psn');

    expect((new PayslipPolicy)->viewAny($user))->toBeFalse();
});
