<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\WeekendRule;
use App\Services\Leave\LeaveCoverageService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    WeekendRule::query()->create([
        'company_id' => $this->company->id,
        'weekend_days' => [0, 6],
    ]);
    session(['company_id' => $this->company->id]);
    $this->employee = Employee::factory()->create(['company_id' => $this->company->id]);
    $this->coverage = app(LeaveCoverageService::class);
});

test('coverageByDate expands approved day leave and ignores pending', function () {
    $type = LeaveType::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Annual',
        'is_paid' => true,
    ]);

    $approved = LeaveRequest::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'leave_type_id' => $type->id,
        'unit' => 'day',
        'start_date' => '2026-07-13',
        'end_date' => '2026-07-15',
        'quantity' => 3,
        'status' => 'approved',
    ]);

    LeaveRequest::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'leave_type_id' => $type->id,
        'unit' => 'day',
        'start_date' => '2026-07-16',
        'end_date' => '2026-07-16',
        'quantity' => 1,
        'status' => 'pending',
    ]);

    $map = $this->coverage->coverageByDate(
        $this->employee->id,
        '2026-07-13',
        '2026-07-17',
    );

    expect($map)->toHaveKeys(['2026-07-13', '2026-07-14', '2026-07-15'])
        ->and($map)->not->toHaveKey('2026-07-16')
        ->and($map['2026-07-13']['request_id'])->toBe($approved->id)
        ->and($map['2026-07-13']['coverage'])->toBe('full')
        ->and($map['2026-07-13']['leave_type_name'])->toBe('Annual')
        ->and($map['2026-07-13']['is_paid'])->toBeTrue();
});

test('coverageByDate maps half_day and hourly coverage', function () {
    $type = LeaveType::factory()->create([
        'company_id' => $this->company->id,
        'requires_balance' => false,
    ]);

    LeaveRequest::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'leave_type_id' => $type->id,
        'unit' => 'half_day',
        'is_half_day' => true,
        'half_day_period' => 'pm',
        'start_date' => '2026-07-20',
        'end_date' => '2026-07-20',
        'quantity' => 0.5,
        'status' => 'approved',
    ]);

    LeaveRequest::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'leave_type_id' => $type->id,
        'unit' => 'hour',
        'start_date' => '2026-07-21',
        'end_date' => '2026-07-21',
        'start_at' => '2026-07-21 09:00:00',
        'end_at' => '2026-07-21 11:00:00',
        'quantity' => 2,
        'status' => 'approved',
    ]);

    $map = $this->coverage->coverageByDate(
        $this->employee->id,
        '2026-07-20',
        '2026-07-21',
    );

    expect($map['2026-07-20']['coverage'])->toBe('pm')
        ->and($map['2026-07-21']['coverage'])->toBe('hours')
        ->and($map['2026-07-21']['start_at'])->not->toBeNull()
        ->and($map['2026-07-21']['end_at'])->not->toBeNull();
});

test('unpaidDayEquivalentInPeriod prorates multi-month leave and converts hours', function () {
    $unpaid = LeaveType::factory()->create([
        'company_id' => $this->company->id,
        'code' => 'UNPAID',
        'is_paid' => false,
        'requires_balance' => false,
    ]);
    $paid = LeaveType::factory()->create([
        'company_id' => $this->company->id,
        'code' => 'PAID',
        'is_paid' => true,
        'requires_balance' => false,
    ]);

    // Mon Jun 29 – Wed Jul 1 2026 (skip weekend Jun 27-28 if spanned)
    LeaveRequest::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'leave_type_id' => $unpaid->id,
        'unit' => 'day',
        'start_date' => '2026-06-29',
        'end_date' => '2026-07-01',
        'quantity' => 3,
        'status' => 'approved',
    ]);

    LeaveRequest::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'leave_type_id' => $paid->id,
        'unit' => 'day',
        'start_date' => '2026-07-02',
        'end_date' => '2026-07-02',
        'quantity' => 1,
        'status' => 'approved',
    ]);

    $shift = Shift::factory()->create([
        'company_id' => $this->company->id,
        'start_time' => '08:00:00',
        'end_time' => '16:00:00',
    ]);
    ShiftAssignment::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'shift_id' => $shift->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-31',
    ]);

    LeaveRequest::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'leave_type_id' => $unpaid->id,
        'unit' => 'hour',
        'start_date' => '2026-07-03',
        'end_date' => '2026-07-03',
        'start_at' => '2026-07-03 08:00:00',
        'end_at' => '2026-07-03 12:00:00',
        'quantity' => 4,
        'status' => 'approved',
    ]);

    // July period: Jul 1 (1 day) + 4h/8h = 0.5 → 1.5; paid ignored
    $july = $this->coverage->unpaidDayEquivalentInPeriod(
        $this->employee->id,
        '2026-07-01',
        '2026-07-31',
    );

    expect($july)->toBe(1.5);

    $june = $this->coverage->unpaidDayEquivalentInPeriod(
        $this->employee->id,
        '2026-06-01',
        '2026-06-30',
    );

    expect($june)->toBe(2.0); // Jun 29, 30
});
