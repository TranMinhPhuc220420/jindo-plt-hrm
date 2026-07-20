<?php

use App\Events\AssetAssigned;
use App\Events\AttendanceCorrectionRequested;
use App\Events\ShiftAssigned;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Shift;
use App\Models\ShiftAssignment;

test('attendance correction requested notifies the manager', function () {
    $company = Company::factory()->create();

    $managerUser = actingUser([
        'can_approve_attendance',
        'can_view_attendance',
        'can_view_own_notifications',
    ], prefix: 'att-mgr');
    $manager = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $managerUser->id,
        'status' => 'active',
    ]);

    $employeeUser = actingUser(['can_view_attendance', 'can_view_own_notifications'], prefix: 'att-emp');
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $employeeUser->id,
        'manager_id' => $manager->id,
        'status' => 'active',
    ]);

    $record = AttendanceRecord::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'status' => 'pending',
    ]);

    $correction = AttendanceCorrection::factory()->create([
        'company_id' => $company->id,
        'attendance_record_id' => $record->id,
        'employee_id' => $employee->id,
        'status' => 'pending',
    ]);

    AttendanceCorrectionRequested::dispatch($correction);

    expect(Notification::query()
        ->where('user_id', $managerUser->id)
        ->where('type', 'attendance.correction_requested')
        ->count())->toBe(1);
});

test('shift assigned notifies the employee', function () {
    $company = Company::factory()->create();
    $user = actingUser(['can_view_own_notifications'], prefix: 'shift-emp');
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'status' => 'active',
    ]);
    $shift = Shift::factory()->create(['company_id' => $company->id]);
    $assignment = ShiftAssignment::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $shift->id,
    ]);

    ShiftAssigned::dispatch($assignment);

    expect(Notification::query()
        ->where('user_id', $user->id)
        ->where('type', 'shift.assigned')
        ->count())->toBe(1);
});

test('asset assigned notifies the employee', function () {
    $company = Company::factory()->create();
    $user = actingUser(['can_view_own_notifications'], prefix: 'asset-emp');
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'status' => 'active',
    ]);
    $asset = Asset::factory()->create(['company_id' => $company->id, 'status' => 'assigned']);
    $assignment = AssetAssignment::factory()->create([
        'company_id' => $company->id,
        'asset_id' => $asset->id,
        'employee_id' => $employee->id,
        'status' => 'active',
    ]);

    AssetAssigned::dispatch($assignment);

    expect(Notification::query()
        ->where('user_id', $user->id)
        ->where('type', 'asset.assigned')
        ->count())->toBe(1);
});

test('employee without user link skips inbox quietly', function () {
    $company = Company::factory()->create();
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => null,
        'status' => 'active',
    ]);
    $shift = Shift::factory()->create(['company_id' => $company->id]);
    $assignment = ShiftAssignment::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $shift->id,
    ]);

    ShiftAssigned::dispatch($assignment);

    expect(Notification::query()->count())->toBe(0);
});
