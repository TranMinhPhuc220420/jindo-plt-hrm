<?php

use App\Events\PayrollApproved;
use App\Events\PayrollCalculated;
use App\Events\PayrollFinalized;
use App\Events\SalaryChanged;
use App\Jobs\GeneratePayslipPdfJob;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\User;
use App\Models\WeekendRule;
use App\Services\Attendance\AttendanceSummaryService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

function seedPayrollAuth(): void
{
    seedAuthCatalog();
}

function payrollUser(array $permissionKeys): User
{
    return actingUser($permissionKeys, prefix: 'pay');
}

function linkPayrollEmployee(User $user, Company $company, array $extra = []): Employee
{
    return Employee::factory()->create(array_merge([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'code' => 'E-PAY-'.uniqid(),
        'status' => 'active',
    ], $extra));
}

test('cannot upsert salary without can_manage_salary', function () {
    $company = Company::factory()->create();
    $user = payrollUser(['can_view_salary']);
    $employee = linkPayrollEmployee($user, $company);

    $this->actingAs($user)
        ->withHeaders(spaJsonHeaders())
        ->putJson('/api/employees/'.$employee->id.'/salary', [
            'amount' => 10000000,
            'effective_from' => '2026-07-01',
            'strategy' => 'monthly',
        ])
        ->assertForbidden();
});

test('salary upsert is audited', function () {
    $company = Company::factory()->create();
    $hr = payrollUser(['can_manage_salary', 'can_view_salary']);
    $employee = Employee::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->putJson('/api/employees/'.$employee->id.'/salary', [
            'amount' => '15000000.00',
            'currency' => 'VND',
            'effective_from' => '2026-07-01',
            'strategy' => 'monthly',
        ])
        ->assertOk()
        ->assertJsonPath('data.amount', '15000000.00');

    expect(AuditLog::query()->where('action', 'payroll.salary_changed')->count())->toBe(1);
});

test('salary upsert fires SalaryChanged', function () {
    Event::fake([SalaryChanged::class]);

    $company = Company::factory()->create();
    $hr = payrollUser(['can_manage_salary', 'can_view_salary']);
    $employee = Employee::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->putJson('/api/employees/'.$employee->id.'/salary', [
            'amount' => '15000000.00',
            'currency' => 'VND',
            'effective_from' => '2026-07-01',
            'strategy' => 'monthly',
        ])
        ->assertOk();

    Event::assertDispatched(SalaryChanged::class, fn ($event) => $event->salary->employee_id === $employee->id);
});

test('employee salaries index can filter to current only', function () {
    $company = Company::factory()->create();
    $hr = payrollUser(['can_manage_salary', 'can_view_salary']);
    $employee = Employee::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    EmployeeSalary::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'amount' => 10000000,
        'effective_from' => '2026-01-01',
        'effective_to' => '2026-06-30',
    ]);

    $current = EmployeeSalary::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'amount' => 12000000,
        'effective_from' => '2026-07-01',
        'effective_to' => null,
    ]);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/employee-salaries?current_only=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $current->id)
        ->assertJsonPath('data.0.amount', '12000000.00');
});

test('calculate uses attendance overtime and unpaid leave', function () {
    $company = Company::factory()->create();
    $hr = payrollUser([
        'can_manage_salary',
        'can_run_payroll',
        'can_approve_payroll',
        'can_view_payroll_history',
        'can_manage_payslips',
        'can_view_salary',
    ]);

    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
        'code' => 'E-PAY-CALC',
    ]);

    EmployeeSalary::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'amount' => 15000000,
        'effective_from' => '2026-07-01',
    ]);

    AttendanceRecord::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'work_date' => '2026-07-10',
        'check_in_at' => '2026-07-10 08:00:00',
        'check_out_at' => '2026-07-10 18:00:00',
        'worked_minutes' => 480,
        'overtime_minutes' => 60,
        'status' => 'approved',
    ]);

    $unpaidType = LeaveType::factory()->create([
        'company_id' => $company->id,
        'code' => 'UNPAID',
        'is_paid' => false,
        'requires_balance' => false,
    ]);

    LeaveRequest::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'leave_type_id' => $unpaidType->id,
        'start_date' => '2026-07-15',
        'end_date' => '2026-07-15',
        'quantity' => 1,
        'status' => 'approved',
    ]);

    $runId = $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/payroll-runs', [
            'name' => 'July 2026',
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/payroll-runs/{$runId}/calculate")
        ->assertOk()
        ->assertJsonPath('data.status', 'calculated');

    $items = $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->getJson("/api/payroll-runs/{$runId}/items")
        ->assertOk()
        ->json('data');

    expect($items)->not->toBeEmpty();

    $components = collect($items[0]['components']);
    expect($components->firstWhere('type', 'overtime'))->not->toBeNull();
    expect($components->firstWhere('label', 'Unpaid leave'))->not->toBeNull();
});

test('payroll prorates unpaid leave across periods and ignores paid leave', function () {
    $company = Company::factory()->create();
    WeekendRule::query()->create([
        'company_id' => $company->id,
        'weekend_days' => [0, 6],
    ]);
    $hr = payrollUser([
        'can_manage_salary',
        'can_run_payroll',
        'can_view_payroll_history',
        'can_view_salary',
    ]);

    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
        'code' => 'E-PAY-LEAVE',
    ]);

    EmployeeSalary::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'amount' => 30000000,
        'effective_from' => '2026-06-01',
    ]);

    $unpaid = LeaveType::factory()->create([
        'company_id' => $company->id,
        'code' => 'UNPAID2',
        'is_paid' => false,
        'requires_balance' => false,
    ]);
    $paid = LeaveType::factory()->create([
        'company_id' => $company->id,
        'code' => 'PAID2',
        'is_paid' => true,
        'requires_balance' => false,
    ]);

    // Jun 29 – Jul 1 = 3 working days; July run should only deduct Jul 1 (1 day = 1_000_000)
    LeaveRequest::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'leave_type_id' => $unpaid->id,
        'unit' => 'day',
        'start_date' => '2026-06-29',
        'end_date' => '2026-07-01',
        'quantity' => 3,
        'status' => 'approved',
    ]);

    LeaveRequest::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'leave_type_id' => $paid->id,
        'unit' => 'day',
        'start_date' => '2026-07-02',
        'end_date' => '2026-07-02',
        'quantity' => 1,
        'status' => 'approved',
    ]);

    $runId = $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/payroll-runs', [
            'name' => 'July leave proration',
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/payroll-runs/{$runId}/calculate")
        ->assertOk();

    $items = $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->getJson("/api/payroll-runs/{$runId}/items")
        ->assertOk()
        ->json('data');

    $unpaidComponent = collect($items[0]['components'])->firstWhere('label', 'Unpaid leave');
    expect($unpaidComponent)->not->toBeNull()
        ->and($unpaidComponent['amount'])->toBe('-1000000.00');
});

test('a failure summarizing attendance surfaces as PAYROLL_CALCULATION_FAILED', function () {
    $company = Company::factory()->create();
    $hr = payrollUser(['can_manage_salary', 'can_run_payroll']);

    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);
    EmployeeSalary::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'amount' => 10000000,
        'effective_from' => '2026-07-01',
    ]);

    $this->mock(AttendanceSummaryService::class, function ($mock) {
        $mock->shouldReceive('summarizeForPayroll')->andThrow(new RuntimeException('attendance backend unavailable'));
    });

    $runId = $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/payroll-runs', [
            'name' => 'August 2026',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/payroll-runs/{$runId}/calculate")
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'PAYROLL_CALCULATION_FAILED');
});

test('approve before calculate returns PAYROLL_NOT_CALCULATED', function () {
    $company = Company::factory()->create();
    $hr = payrollUser(['can_run_payroll', 'can_approve_payroll', 'can_view_payroll_history']);

    $run = PayrollRun::factory()->create([
        'company_id' => $company->id,
        'status' => 'draft',
    ]);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/payroll-runs/'.$run->id.'/approve')
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'PAYROLL_NOT_CALCULATED');
});

test('duplicate period returns PAYROLL_DUPLICATE_PERIOD', function () {
    $company = Company::factory()->create();
    $hr = payrollUser(['can_run_payroll', 'can_view_payroll_history']);

    PayrollRun::factory()->create([
        'company_id' => $company->id,
        'period_start' => '2026-07-01',
        'period_end' => '2026-07-31',
        'run_type' => 'regular',
    ]);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/payroll-runs', [
            'name' => 'July again',
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
        ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'PAYROLL_DUPLICATE_PERIOD');
});

test('payroll run lifecycle fires domain events and queues payslip PDF generation', function () {
    Event::fake([PayrollCalculated::class, PayrollApproved::class, PayrollFinalized::class]);
    Queue::fake();

    $company = Company::factory()->create();
    $hr = payrollUser([
        'can_manage_salary',
        'can_run_payroll',
        'can_approve_payroll',
        'can_view_payroll_history',
        'can_manage_payslips',
    ]);

    $employee = Employee::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    EmployeeSalary::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'amount' => 10000000,
        'effective_from' => '2026-07-01',
    ]);

    $runId = $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/payroll-runs', [
            'name' => 'July Events',
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson("/api/payroll-runs/{$runId}/calculate")->assertOk();
    Event::assertDispatched(PayrollCalculated::class, fn ($event) => $event->payrollRun->id === $runId);

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson("/api/payroll-runs/{$runId}/approve")->assertOk();
    Event::assertDispatched(PayrollApproved::class, fn ($event) => $event->payrollRun->id === $runId);

    $this->actingAs($hr)->withHeaders(spaJsonHeaders())
        ->postJson("/api/payroll-runs/{$runId}/finalize")->assertOk();
    Event::assertDispatched(PayrollFinalized::class, fn ($event) => $event->payrollRun->id === $runId);

    $payslipId = Payslip::query()->where('payroll_run_id', $runId)->value('id');
    Queue::assertPushedOn('payroll', GeneratePayslipPdfJob::class, fn ($job) => $job->payslipId === $payslipId);
});

test('finalize is immutable', function () {
    $company = Company::factory()->create();
    $hr = payrollUser([
        'can_manage_salary',
        'can_run_payroll',
        'can_approve_payroll',
        'can_view_payroll_history',
        'can_manage_payslips',
    ]);

    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);

    EmployeeSalary::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'amount' => 10000000,
        'effective_from' => '2026-07-01',
    ]);

    $runId = $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson('/api/payroll-runs', [
            'name' => 'July Final',
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
        ])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/payroll-runs/{$runId}/calculate")
        ->assertOk();

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/payroll-runs/{$runId}/approve")
        ->assertOk();

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/payroll-runs/{$runId}/finalize")
        ->assertOk()
        ->assertJsonPath('data.status', 'finalized');

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->postJson("/api/payroll-runs/{$runId}/calculate")
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'PAYROLL_ALREADY_FINALIZED');

    expect(Payslip::query()->where('payroll_run_id', $runId)->count())->toBe(1);
});

test('employee cannot view another payslip', function () {
    $company = Company::factory()->create();

    $ownerUser = payrollUser(['can_view_salary']);
    $owner = linkPayrollEmployee($ownerUser, $company);

    $otherUser = payrollUser(['can_view_salary']);
    $other = linkPayrollEmployee($otherUser, $company);

    $run = PayrollRun::factory()->create([
        'company_id' => $company->id,
        'status' => 'finalized',
    ]);

    $item = PayrollItem::factory()->create([
        'company_id' => $company->id,
        'payroll_run_id' => $run->id,
        'employee_id' => $owner->id,
    ]);

    $payslip = Payslip::factory()->create([
        'company_id' => $company->id,
        'payroll_run_id' => $run->id,
        'payroll_item_id' => $item->id,
        'employee_id' => $owner->id,
    ]);

    $this->actingAs($otherUser)
        ->withHeaders(spaJsonHeaders())
        ->getJson('/api/payslips/'.$payslip->id)
        ->assertForbidden();
});

test('update draft payroll run succeeds', function () {
    $company = Company::factory()->create();
    $hr = payrollUser(['can_run_payroll', 'can_view_payroll_history']);

    $run = PayrollRun::factory()->create([
        'company_id' => $company->id,
        'name' => 'July draft',
        'period_start' => '2026-07-01',
        'period_end' => '2026-07-31',
        'status' => 'draft',
    ]);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->putJson('/api/payroll-runs/'.$run->id, [
            'name' => 'July renamed',
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-15',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'July renamed')
        ->assertJsonPath('data.period_end', '2026-07-15')
        ->assertJsonPath('data.status', 'draft');

    expect(AuditLog::query()->where('action', 'payroll.run_updated')->exists())->toBeTrue();
});

test('update non-draft payroll run returns PAYROLL_NOT_DRAFT', function () {
    $company = Company::factory()->create();
    $hr = payrollUser(['can_run_payroll', 'can_view_payroll_history']);

    $cases = [
        ['calculated', '2026-08-01', '2026-08-31'],
        ['approved', '2026-09-01', '2026-09-30'],
        ['finalized', '2026-10-01', '2026-10-31'],
    ];

    foreach ($cases as [$status, $start, $end]) {
        $run = PayrollRun::factory()->create([
            'company_id' => $company->id,
            'period_start' => $start,
            'period_end' => $end,
            'status' => $status,
            'name' => "Run {$status}",
        ]);

        $this->actingAs($hr)
            ->withHeaders(spaJsonHeaders())
            ->putJson('/api/payroll-runs/'.$run->id, [
                'name' => 'Nope',
                'period_start' => $start,
                'period_end' => $end,
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PAYROLL_NOT_DRAFT');
    }
});

test('update colliding period returns PAYROLL_DUPLICATE_PERIOD', function () {
    $company = Company::factory()->create();
    $hr = payrollUser(['can_run_payroll', 'can_view_payroll_history']);

    PayrollRun::factory()->create([
        'company_id' => $company->id,
        'period_start' => '2026-07-01',
        'period_end' => '2026-07-31',
        'run_type' => 'regular',
        'status' => 'draft',
    ]);

    $run = PayrollRun::factory()->create([
        'company_id' => $company->id,
        'period_start' => '2026-08-01',
        'period_end' => '2026-08-31',
        'run_type' => 'regular',
        'status' => 'draft',
    ]);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->putJson('/api/payroll-runs/'.$run->id, [
            'name' => 'Clash',
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
        ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'PAYROLL_DUPLICATE_PERIOD');
});

test('delete pre-finalized payroll runs succeeds', function () {
    $company = Company::factory()->create();
    $hr = payrollUser(['can_run_payroll', 'can_view_payroll_history']);

    $statuses = [
        'draft' => ['2026-01-01', '2026-01-31'],
        'calculated' => ['2026-02-01', '2026-02-28'],
        'approved' => ['2026-03-01', '2026-03-31'],
    ];

    foreach ($statuses as $status => [$start, $end]) {
        $run = PayrollRun::factory()->create([
            'company_id' => $company->id,
            'status' => $status,
            'period_start' => $start,
            'period_end' => $end,
        ]);

        $this->actingAs($hr)
            ->withHeaders(spaJsonHeaders())
            ->deleteJson('/api/payroll-runs/'.$run->id)
            ->assertOk();

        expect(PayrollRun::query()->whereKey($run->id)->exists())->toBeFalse();
    }

    expect(AuditLog::query()->where('action', 'payroll.run_deleted')->count())->toBe(3);
});

test('delete finalized payroll run returns PAYROLL_ALREADY_FINALIZED', function () {
    $company = Company::factory()->create();
    $hr = payrollUser(['can_run_payroll', 'can_view_payroll_history']);

    $run = PayrollRun::factory()->create([
        'company_id' => $company->id,
        'status' => 'finalized',
    ]);

    $this->actingAs($hr)
        ->withHeaders(spaJsonHeaders())
        ->deleteJson('/api/payroll-runs/'.$run->id)
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'PAYROLL_ALREADY_FINALIZED');

    expect(PayrollRun::query()->whereKey($run->id)->exists())->toBeTrue();
});
