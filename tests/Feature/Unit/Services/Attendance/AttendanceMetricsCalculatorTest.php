<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\OvertimeRule;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Services\Attendance\AttendanceMetricsCalculator;
use Carbon\CarbonImmutable;

// The company timezone defaults to Asia/Ho_Chi_Minh (see SettingsDefaults), while
// app.timezone is UTC — check-in/out instants must be built in the company's
// timezone so they line up with the scheduled shift window under test.
function atTz(string $date, string $time): CarbonImmutable
{
    return CarbonImmutable::parse($date.' '.$time, 'Asia/Ho_Chi_Minh');
}

function metricsCalculator(): AttendanceMetricsCalculator
{
    return app(AttendanceMetricsCalculator::class);
}

/**
 * @return array{0: Company, 1: Employee, 2: Shift}
 */
function setupDayShift(array $shiftOverrides = []): array
{
    $company = Company::factory()->create();
    $employee = Employee::factory()->create(['company_id' => $company->id]);
    $shift = Shift::factory()->create(array_merge([
        'company_id' => $company->id,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'break_minutes' => 60,
    ], $shiftOverrides));

    ShiftAssignment::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'shift_id' => $shift->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    return [$company, $employee, $shift];
}

test('on-time check-in/out has no late, early, or overtime minutes', function () {
    [, $employee] = setupDayShift();

    $result = metricsCalculator()->compute(
        $employee->id,
        '2026-06-01',
        atTz('2026-06-01', '08:00:00'),
        atTz('2026-06-01', '17:00:00'),
    );

    expect($result)->toBe([
        'worked_minutes' => 480,
        'late_minutes' => 0,
        'early_leave_minutes' => 0,
        'overtime_minutes' => 0,
        'break_minutes' => 60,
    ]);
});

test('late check-in is recorded against the scheduled start', function () {
    [, $employee] = setupDayShift();

    $result = metricsCalculator()->compute(
        $employee->id,
        '2026-06-01',
        atTz('2026-06-01', '08:15:00'),
        atTz('2026-06-01', '17:00:00'),
    );

    expect($result['late_minutes'])->toBe(15);
    expect($result['worked_minutes'])->toBe(465);
    expect($result['early_leave_minutes'])->toBe(0);
    expect($result['overtime_minutes'])->toBe(0);
});

test('early checkout is recorded against the scheduled end', function () {
    [, $employee] = setupDayShift();

    $result = metricsCalculator()->compute(
        $employee->id,
        '2026-06-01',
        atTz('2026-06-01', '08:00:00'),
        atTz('2026-06-01', '16:30:00'),
    );

    expect($result['early_leave_minutes'])->toBe(30);
    expect($result['worked_minutes'])->toBe(450);
    expect($result['late_minutes'])->toBe(0);
    expect($result['overtime_minutes'])->toBe(0);
});

test('checking out after the scheduled end counts as overtime with no rule configured', function () {
    [, $employee] = setupDayShift();

    $result = metricsCalculator()->compute(
        $employee->id,
        '2026-06-01',
        atTz('2026-06-01', '08:00:00'),
        atTz('2026-06-01', '19:00:00'),
    );

    expect($result['overtime_minutes'])->toBe(120);
    expect($result['worked_minutes'])->toBe(600);
    expect($result['early_leave_minutes'])->toBe(0);
});

test('overtime only starts after the configured grace period', function () {
    [$company, $employee] = setupDayShift();
    OvertimeRule::factory()->create([
        'company_id' => $company->id,
        'applies_after_minutes' => 30,
        'is_active' => true,
    ]);

    $withinGrace = metricsCalculator()->compute(
        $employee->id,
        '2026-06-01',
        atTz('2026-06-01', '08:00:00'),
        atTz('2026-06-01', '17:20:00'),
    );
    expect($withinGrace['overtime_minutes'])->toBe(0);

    $pastGrace = metricsCalculator()->compute(
        $employee->id,
        '2026-06-01',
        atTz('2026-06-01', '08:00:00'),
        atTz('2026-06-01', '17:45:00'),
    );
    expect($pastGrace['overtime_minutes'])->toBe(15);
});

test('a missing check-out yields zero worked minutes but still records lateness', function () {
    [, $employee] = setupDayShift();

    $result = metricsCalculator()->compute(
        $employee->id,
        '2026-06-01',
        atTz('2026-06-01', '08:15:00'),
        null,
    );

    expect($result['worked_minutes'])->toBe(0);
    expect($result['late_minutes'])->toBe(15);
    expect($result['early_leave_minutes'])->toBe(0);
    expect($result['overtime_minutes'])->toBe(0);
});

test('a night shift crossing midnight is handled without early leave or overtime', function () {
    [, $employee] = setupDayShift([
        'start_time' => '22:00:00',
        'end_time' => '06:00:00',
        'kind' => 'night',
        'is_night' => true,
        'break_minutes' => 0,
    ]);

    $result = metricsCalculator()->compute(
        $employee->id,
        '2026-06-01',
        atTz('2026-06-01', '22:00:00'),
        atTz('2026-06-02', '06:00:00'),
    );

    expect($result)->toBe([
        'worked_minutes' => 480,
        'late_minutes' => 0,
        'early_leave_minutes' => 0,
        'overtime_minutes' => 0,
        'break_minutes' => 0,
    ]);
});

test('an explicit break override replaces the shift default break minutes', function () {
    [, $employee] = setupDayShift(['break_minutes' => 60]);

    $result = metricsCalculator()->compute(
        $employee->id,
        '2026-06-01',
        atTz('2026-06-01', '08:00:00'),
        atTz('2026-06-01', '17:00:00'),
        90,
    );

    expect($result['break_minutes'])->toBe(90);
    expect($result['worked_minutes'])->toBe(450);
});

test('with no shift assigned for the day, only raw worked minutes are computed', function () {
    $company = Company::factory()->create();
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    $result = metricsCalculator()->compute(
        $employee->id,
        '2026-06-01',
        atTz('2026-06-01', '08:00:00'),
        atTz('2026-06-01', '17:00:00'),
    );

    expect($result)->toBe([
        'worked_minutes' => 540,
        'late_minutes' => 0,
        'early_leave_minutes' => 0,
        'overtime_minutes' => 0,
        'break_minutes' => 0,
    ]);
});
