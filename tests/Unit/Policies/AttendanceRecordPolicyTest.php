<?php

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Policies\AttendanceRecordPolicy;

beforeEach(function () {
    seedAuthCatalog();
});

test('viewAny and view require can_view_attendance', function () {
    $company = Company::factory()->create();
    $employee = Employee::factory()->create(['company_id' => $company->id]);
    $record = AttendanceRecord::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
    ]);

    $viewer = actingUser(['can_view_attendance'], prefix: 'atv');
    $denied = actingUser(['can_check_in_out'], prefix: 'atd');

    $policy = new AttendanceRecordPolicy;

    expect($policy->viewAny($viewer))->toBeTrue()
        ->and($policy->view($viewer, $record))->toBeTrue()
        ->and($policy->viewAny($denied))->toBeFalse();
});

test('checkInOut requires can_check_in_out', function () {
    expect((new AttendanceRecordPolicy)->checkInOut(actingUser([], prefix: 'cin0')))->toBeFalse()
        ->and((new AttendanceRecordPolicy)->checkInOut(actingUser(['can_check_in_out'], prefix: 'cin1')))->toBeTrue();
});

test('approve requires can_approve_attendance', function () {
    $company = Company::factory()->create();
    $record = AttendanceRecord::factory()->create(['company_id' => $company->id]);

    expect((new AttendanceRecordPolicy)->approve(actingUser(['can_view_attendance'], prefix: 'ata'), $record))->toBeFalse()
        ->and((new AttendanceRecordPolicy)->approve(actingUser(['can_approve_attendance'], prefix: 'ata2'), $record))->toBeTrue();
});

test('manage requires can_manage_attendance', function () {
    expect((new AttendanceRecordPolicy)->manage(actingUser(['can_view_attendance'], prefix: 'atm')))->toBeFalse()
        ->and((new AttendanceRecordPolicy)->manage(actingUser(['can_manage_attendance'], prefix: 'atm2')))->toBeTrue();
});
