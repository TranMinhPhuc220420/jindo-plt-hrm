<?php

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\OvertimeRule;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Services\Attendance\AttendanceSummaryService;
use App\Services\Settings\SettingsService;

function seedAttendanceAuth(): void
{
    seedAuthCatalog();
}

function attendanceUser(array $permissionKeys): User
{
    return actingUser($permissionKeys, prefix: 'att');
}

function linkEmployee(User $user, Company $company): Employee
{
    return Employee::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'code' => 'E-ATT-'.uniqid(),
    ]);
}

test('cannot check in without can_check_in_out', function () {
    Company::factory()->create();
    $user = attendanceUser([]);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-in')
        ->assertForbidden();
});

test('employee can check in and out with metrics from shift', function () {
    $company = Company::factory()->create();
    $user = attendanceUser([
        'can_check_in_out',
        'can_view_attendance',
    ]);
    $employee = linkEmployee($user, $company);

    $shift = Shift::factory()->create([
        'company_id' => $company->id,
        'code' => 'MOR-ATT',
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'break_minutes' => 60,
    ]);
    ShiftAssignment::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $shift->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
    ]);
    OvertimeRule::factory()->create([
        'company_id' => $company->id,
        'code' => 'STANDARD',
        'applies_after_minutes' => 0,
        'is_active' => true,
    ]);

    $in = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-in', [
            'worked_at' => '2026-07-16T08:15:00+07:00',
        ]);

    $in->assertCreated()
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.late_minutes', 15);

    expect(AuditLog::query()->where('action', 'attendance.checked_in')->count())->toBe(1);

    $out = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-out', [
            'worked_at' => '2026-07-16T17:30:00+07:00',
        ]);

    $out->assertOk()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.overtime_minutes', 30);
});

test('double check-in returns ATTENDANCE_ALREADY_CHECKED_IN', function () {
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out']);
    linkEmployee($user, $company);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-in', [
            'worked_at' => '2026-07-16T08:00:00+07:00',
        ])
        ->assertCreated();

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-in', [
            'worked_at' => '2026-07-16T08:05:00+07:00',
        ])
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'ATTENDANCE_ALREADY_CHECKED_IN');
});

test('checking out without an open check-in returns ATTENDANCE_INVALID_TRANSITION', function () {
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out']);
    linkEmployee($user, $company);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-out', [
            'worked_at' => '2026-07-16T17:00:00+07:00',
        ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'ATTENDANCE_INVALID_TRANSITION');
});

test('correction approve and reject with audit', function () {
    $company = Company::factory()->create();
    $employeeUser = attendanceUser([
        'can_check_in_out',
        'can_view_attendance',
        'can_request_attendance_correction',
    ]);
    $employee = linkEmployee($employeeUser, $company);

    $approver = attendanceUser([
        'can_view_attendance',
        'can_approve_attendance',
    ]);

    $this->actingAs($employeeUser)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-in', [
            'worked_at' => '2026-07-16T09:00:00+07:00',
        ])
        ->assertCreated();

    $this->actingAs($employeeUser)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-out', [
            'worked_at' => '2026-07-16T17:00:00+07:00',
        ])
        ->assertOk();

    $recordId = $this->actingAs($employeeUser)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/attendance/records')
        ->assertOk()
        ->json('data.0.id');

    $correctionId = $this->actingAs($employeeUser)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/corrections', [
            'attendance_record_id' => $recordId,
            'proposed_check_in_at' => '2026-07-16T08:00:00+07:00',
            'proposed_check_out_at' => '2026-07-16T17:00:00+07:00',
            'reason' => 'Forgot earlier punch',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($approver)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/attendance/corrections/{$correctionId}/approve")
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');

    expect(AuditLog::query()->where('action', 'attendance.correction_approved')->count())->toBe(1);

    // Second correction then reject
    $this->actingAs($employeeUser)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-in', [
            'worked_at' => '2026-07-17T08:00:00+07:00',
        ])
        ->assertCreated();
    $this->actingAs($employeeUser)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-out', [
            'worked_at' => '2026-07-17T17:00:00+07:00',
        ])
        ->assertOk();

    $record2 = $this->actingAs($employeeUser)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/attendance/records?date_from=2026-07-17&date_to=2026-07-17')
        ->json('data.0.id');

    $c2 = $this->actingAs($employeeUser)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/corrections', [
            'attendance_record_id' => $record2,
            'proposed_check_in_at' => '2026-07-17T08:30:00+07:00',
            'proposed_check_out_at' => '2026-07-17T17:00:00+07:00',
            'reason' => 'Wrong time',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($approver)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/attendance/corrections/{$c2}/reject", [
            'review_note' => 'Not enough evidence',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'rejected');

    expect($employee->id)->toBeInt();
});

test('locked period blocks check-in and correction', function () {
    $company = Company::factory()->create();
    $user = attendanceUser([
        'can_check_in_out',
        'can_view_attendance',
        'can_request_attendance_correction',
        'can_manage_attendance',
    ]);
    linkEmployee($user, $company);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-in', [
            'worked_at' => '2026-07-16T08:00:00+07:00',
        ])
        ->assertCreated();

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-out', [
            'worked_at' => '2026-07-16T17:00:00+07:00',
        ])
        ->assertOk();

    $recordId = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/attendance/records')
        ->json('data.0.id');

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/period/lock', [
            'date_from' => '2026-07-16',
            'date_to' => '2026-07-16',
        ])
        ->assertOk()
        ->assertJsonPath('data.locked_count', 1);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/corrections', [
            'attendance_record_id' => $recordId,
            'proposed_check_in_at' => '2026-07-16T08:00:00+07:00',
            'proposed_check_out_at' => '2026-07-16T18:00:00+07:00',
            'reason' => 'Too late',
        ])
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'ATTENDANCE_PERIOD_LOCKED');

    // New check-in on locked date after wiping uniqueness - create locked empty day:
    // For same date already locked with check-in, trying check-in again hits ALREADY or LOCKED.
    // Lock another empty day by creating then locking:
    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-in', [
            'worked_at' => '2026-07-18T08:00:00+07:00',
        ])
        ->assertCreated();

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/period/lock', [
            'date_from' => '2026-07-18',
            'date_to' => '2026-07-18',
        ])
        ->assertOk();

    // Check-out on locked open record should fail
    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-out', [
            'worked_at' => '2026-07-18T17:00:00+07:00',
        ])
        ->assertStatus(409)
        ->assertJsonPath('error_code', 'ATTENDANCE_PERIOD_LOCKED');
});

test('summary aggregates period minutes', function () {
    $company = Company::factory()->create();
    $user = attendanceUser([
        'can_check_in_out',
        'can_view_attendance',
    ]);
    $employee = linkEmployee($user, $company);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-in', [
            'worked_at' => '2026-07-16T08:00:00+07:00',
        ])
        ->assertCreated();
    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-out', [
            'worked_at' => '2026-07-16T17:00:00+07:00',
        ])
        ->assertOk();

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/attendance/summary?employee_id='.$employee->id.'&period_start=2026-07-01&period_end=2026-07-31')
        ->assertOk()
        ->assertJsonPath('data.employee_id', $employee->id)
        ->assertJsonPath('data.days_present', 1);
});

test('AttendanceSummaryService smoke without payroll writes', function () {
    $company = Company::factory()->create();
    $user = attendanceUser(['can_view_attendance', 'can_approve_attendance']);
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    AttendanceRecord::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'work_date' => '2026-07-16',
        'worked_minutes' => 480,
        'late_minutes' => 10,
        'overtime_minutes' => 20,
        'status' => 'pending',
        'check_in_at' => '2026-07-16 08:00:00',
        'check_out_at' => '2026-07-16 17:00:00',
    ]);

    session(['company_id' => $company->id]);

    $summary = app(AttendanceSummaryService::class)->summarize(
        $user,
        $employee->id,
        '2026-07-01',
        '2026-07-31',
    );

    expect($summary['worked_minutes'])->toBe(480)
        ->and($summary['late_minutes'])->toBe(10)
        ->and($summary['overtime_minutes'])->toBe(20)
        ->and($summary['days_present'])->toBe(1);

    expect(PayrollRun::query()->count())->toBe(0)
        ->and(Payslip::query()->count())->toBe(0);
});

test('record approve path works', function () {
    $company = Company::factory()->create();
    $user = attendanceUser([
        'can_check_in_out',
        'can_view_attendance',
        'can_approve_attendance',
    ]);
    linkEmployee($user, $company);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-in', [
            'worked_at' => '2026-07-16T08:00:00+07:00',
        ])
        ->assertCreated();
    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-out', [
            'worked_at' => '2026-07-16T17:00:00+07:00',
        ])
        ->assertOk();

    $id = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/attendance/records')
        ->json('data.0.id');

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/attendance/records/{$id}/approve")
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');
});

test('naive datetime-local correction is interpreted in company timezone', function () {
    $company = Company::factory()->create();
    app(SettingsService::class)->seedDefaultsForCompany($company->id);

    $user = attendanceUser([
        'can_check_in_out',
        'can_view_attendance',
        'can_request_attendance_correction',
    ]);
    linkEmployee($user, $company);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-in', [
            'worked_at' => '2026-07-16T09:00:00+07:00',
        ])
        ->assertCreated();
    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-out', [
            'worked_at' => '2026-07-16T18:00:00+07:00',
        ])
        ->assertOk();

    $recordId = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/attendance/records')
        ->assertOk()
        ->json('data.0.id');

    // UI DateTimePicker sends naive local wall-clock (company TZ Asia/Ho_Chi_Minh).
    $correction = $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/corrections', [
            'attendance_record_id' => $recordId,
            'proposed_check_in_at' => '2026-07-16T08:00',
            'proposed_check_out_at' => '2026-07-16T17:00',
            'reason' => 'Fix to standard hours',
        ])
        ->assertCreated()
        ->json('data');

    // Stored as UTC instants equivalent to 08:00 / 17:00 ICT.
    expect($correction['proposed_check_in_at'])->toContain('01:00:00')
        ->and($correction['proposed_check_out_at'])->toContain('10:00:00');
});

test('full-day approved leave suppresses late and overtime metrics', function () {
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out', 'can_view_attendance']);
    $employee = linkEmployee($user, $company);

    $shift = Shift::factory()->create([
        'company_id' => $company->id,
        'code' => 'MOR-LEAVE',
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'break_minutes' => 60,
    ]);
    ShiftAssignment::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $shift->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
    ]);
    OvertimeRule::factory()->create([
        'company_id' => $company->id,
        'code' => 'STD-LEAVE',
        'applies_after_minutes' => 0,
        'is_active' => true,
    ]);

    $type = LeaveType::factory()->create([
        'company_id' => $company->id,
        'requires_balance' => false,
    ]);
    LeaveRequest::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'leave_type_id' => $type->id,
        'unit' => 'day',
        'start_date' => '2026-07-16',
        'end_date' => '2026-07-16',
        'quantity' => 1,
        'status' => 'approved',
    ]);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-in', [
            'worked_at' => '2026-07-16T08:15:00+07:00',
        ])
        ->assertCreated()
        ->assertJsonPath('data.late_minutes', 0);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-out', [
            'worked_at' => '2026-07-16T17:30:00+07:00',
        ])
        ->assertOk()
        ->assertJsonPath('data.overtime_minutes', 0)
        ->assertJsonPath('data.early_leave_minutes', 0);
});

test('am half-day leave evaluates late against afternoon window', function () {
    $company = Company::factory()->create();
    $user = attendanceUser(['can_check_in_out', 'can_view_attendance']);
    $employee = linkEmployee($user, $company);

    $shift = Shift::factory()->create([
        'company_id' => $company->id,
        'code' => 'MOR-HALF',
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'break_minutes' => 0,
    ]);
    ShiftAssignment::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $shift->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
    ]);

    $type = LeaveType::factory()->create([
        'company_id' => $company->id,
        'requires_balance' => false,
    ]);
    LeaveRequest::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'leave_type_id' => $type->id,
        'unit' => 'half_day',
        'is_half_day' => true,
        'half_day_period' => 'am',
        'start_date' => '2026-07-17',
        'end_date' => '2026-07-17',
        'quantity' => 0.5,
        'status' => 'approved',
    ]);

    // Midpoint of 08:00–17:00 is 12:30; arrive 13:00 → 30 late.
    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/attendance/check-in', [
            'worked_at' => '2026-07-17T13:00:00+07:00',
        ])
        ->assertCreated()
        ->assertJsonPath('data.late_minutes', 30);
});
