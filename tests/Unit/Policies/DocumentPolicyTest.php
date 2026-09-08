<?php

use App\Models\Company;
use App\Models\Document;
use App\Models\Employee;
use App\Policies\DocumentPolicy;

beforeEach(function () {
    seedAuthCatalog();
});

test('company document view requires company document permission', function () {
    $company = Company::factory()->create();
    $document = Document::factory()->create([
        'company_id' => $company->id,
        'owner_type' => 'company',
        'owner_id' => null,
    ]);

    $allowed = actingUser(['can_view_company_documents'], prefix: 'dc');
    $denied = actingUser(['can_upload_own_documents'], prefix: 'dd');

    $policy = new DocumentPolicy;

    expect($policy->view($allowed, $document))->toBeTrue()
        ->and($policy->view($denied, $document))->toBeFalse();
});

test('employee can view and create own documents with can_upload_own_documents', function () {
    $company = Company::factory()->create();
    [$user, $employee] = actingUserInCompany($company, ['can_upload_own_documents'], 'down');
    $document = Document::factory()->create([
        'company_id' => $company->id,
        'owner_type' => 'employee',
        'owner_id' => $employee->id,
    ]);

    $policy = new DocumentPolicy;

    expect($policy->view($user, $document))->toBeTrue()
        ->and($policy->create($user, 'employee', $employee->id))->toBeTrue()
        ->and($policy->create($user, 'company'))->toBeFalse();
});

test('peer cannot manage another employee document with only upload-own', function () {
    $company = Company::factory()->create();
    [$peer] = actingUserInCompany($company, ['can_upload_own_documents'], 'dpeer');
    $other = Employee::factory()->create(['company_id' => $company->id]);
    $document = Document::factory()->create([
        'company_id' => $company->id,
        'owner_type' => 'employee',
        'owner_id' => $other->id,
    ]);

    $policy = new DocumentPolicy;

    expect($policy->view($peer, $document))->toBeFalse()
        ->and($policy->delete($peer, $document))->toBeFalse();
});

test('hr with manage employee documents can update any employee document', function () {
    $company = Company::factory()->create();
    [$hr] = actingUserInCompany($company, ['can_manage_employee_documents'], 'dhr');
    $other = Employee::factory()->create(['company_id' => $company->id]);
    $document = Document::factory()->create([
        'company_id' => $company->id,
        'owner_type' => 'employee',
        'owner_id' => $other->id,
    ]);

    expect((new DocumentPolicy)->update($hr, $document))->toBeTrue();
});
