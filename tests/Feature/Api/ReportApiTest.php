<?php

use App\Exceptions\DomainException;
use App\Jobs\GenerateReportExportJob;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Permission;
use App\Models\ReportExport;
use App\Models\User;
use App\Services\Report\ReportExportService;
use App\Services\Report\ReportService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

function reportUser(array $permissionKeys): User
{
    return actingUser($permissionKeys, prefix: 'report');
}

test('payroll report is forbidden without payroll report permission', function () {
    Company::factory()->create();
    $user = reportUser(['can_view_attendance_reports']);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->getJson('/api/reports/payroll')
        ->assertStatus(403)
        ->assertJsonPath('error_code', 'REPORT_FORBIDDEN');
});

test('employee report returns rows for authorized user', function () {
    $company = Company::factory()->create();
    $user = reportUser(['can_view_employee_reports']);
    Employee::factory()->count(2)->create(['company_id' => $company->id, 'status' => 'active']);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->getJson('/api/reports/employees')
        ->assertOk()
        ->assertJsonCount(2, 'data.rows');
});

test('attendance report is scoped and readable', function () {
    Company::factory()->create();
    $user = reportUser(['can_view_attendance_reports']);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->getJson('/api/reports/attendance?date_from=2026-07-01&date_to=2026-07-31')
        ->assertOk()
        ->assertJsonPath('meta.filters.date_from', '2026-07-01');
});

test('export queues then becomes ready with sync queue', function () {
    Storage::fake('local');
    $company = Company::factory()->create();
    $user = reportUser(['can_export_reports', 'can_view_employee_reports']);
    Employee::factory()->create(['company_id' => $company->id]);

    $create = $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->postJson('/api/reports/exports', [
            'report' => 'employees',
            'format' => 'csv',
            'filters' => [],
        ])
        ->assertStatus(202);

    $exportId = $create->json('data.id');

    $status = $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->getJson('/api/reports/exports/'.$exportId)
        ->assertOk()
        ->assertJsonPath('data.status', 'ready');

    $path = $status->json('data.path');
    expect($path)->not->toBeNull();
    Storage::disk('local')->assertExists($path);
});

test('export without report permission fails during generation', function () {
    Storage::fake('local');
    Company::factory()->create();
    $user = reportUser(['can_export_reports']); // no payroll view permission

    $create = $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->postJson('/api/reports/exports', [
            'report' => 'payroll',
            'format' => 'csv',
        ])
        ->assertStatus(202);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->getJson('/api/reports/exports/'.$create->json('data.id'))
        ->assertOk()
        ->assertJsonPath('data.status', 'failed');

    expect(ReportExport::query()->value('error_message'))->not->toBeNull();
});

test('requesting an export queues GenerateReportExportJob on the reports queue', function () {
    Queue::fake();
    $company = Company::factory()->create();
    $user = reportUser(['can_export_reports', 'can_view_employee_reports']);
    Employee::factory()->create(['company_id' => $company->id]);

    $exportId = $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->postJson('/api/reports/exports', ['report' => 'employees', 'format' => 'csv'])
        ->assertStatus(202)
        ->json('data.id');

    Queue::assertPushedOn('reports', GenerateReportExportJob::class, fn ($job) => $job->exportId === $exportId);
});

test('export requires export permission', function () {
    Company::factory()->create();
    $user = reportUser(['can_view_employee_reports']);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->postJson('/api/reports/exports', ['report' => 'employees'])
        ->assertStatus(403);
});

test('ReportService::generate defensively rejects an unknown report type', function () {
    Company::factory()->create();
    $user = reportUser(['can_view_employee_reports']);

    try {
        app(ReportService::class)->generate('not-a-real-report', [], $user);
        $this->fail('Expected a DomainException to be thrown.');
    } catch (DomainException $e) {
        expect($e->errorCode())->toBe('REPORT_FILTER_INVALID');
    }
});

// Note: report/format are both validated by StoreReportExportRequest's
// Rule::in() before the request ever reaches ReportExportService, so this
// exercises the service's own defensive guard directly rather than via HTTP.
test('ReportExportService::create defensively rejects an unknown report type', function () {
    Company::factory()->create();
    $user = reportUser(['can_export_reports']);

    try {
        app(ReportExportService::class)->create(['report' => 'not-a-real-report'], $user);
        $this->fail('Expected a DomainException to be thrown.');
    } catch (DomainException $e) {
        expect($e->errorCode())->toBe('REPORT_FILTER_INVALID');
    }
});

test('dashboard summary returns company scope for report viewers', function () {
    $company = Company::factory()->create();
    $user = reportUser(['can_view_employee_reports']);

    $active = Employee::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
        'hired_at' => now()->toDateString(),
    ]);
    Employee::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
        'hired_at' => now()->subMonths(2)->toDateString(),
    ]);

    AttendanceRecord::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $active->id,
        'work_date' => now()->toDateString(),
        'check_in_at' => now()->setTime(8, 0),
    ]);

    Notification::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'read_at' => null,
    ]);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->getJson('/api/dashboard/summary')
        ->assertOk()
        ->assertJsonPath('data.scope', 'company')
        ->assertJsonStructure([
            'data' => [
                'scope',
                'active_employees',
                'attendance_today_rate',
                'pending_leave_requests',
                'new_hires_month',
                'open_payroll_runs',
                'unread_notifications',
                'attendance_last_7_days',
                'employees_by_status',
                'employees_by_department',
                'recent_hires',
                'pending_actions',
                'upcoming',
                'recent_activity',
            ],
        ])
        ->assertJsonPath('data.active_employees', 2)
        ->assertJsonPath('data.new_hires_month', 1)
        ->assertJsonPath('data.unread_notifications', 1)
        ->assertJsonPath('data.attendance_today_rate', 0.5);

    expect(count($this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->getJson('/api/dashboard/summary')
        ->json('data.attendance_last_7_days')))->toBe(7);
});

test('dashboard summary returns self scope without company aggregates', function () {
    $company = Company::factory()->create();
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);
    // Another employee must not leak into self dashboard aggregates.
    Employee::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);

    $user = actingUser(['can_view_own_notifications', 'can_view_attendance'], $employee, 'dash_emp');

    AttendanceRecord::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'work_date' => now()->toDateString(),
        'check_in_at' => now()->setTime(8, 0),
    ]);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->getJson('/api/dashboard/summary')
        ->assertOk()
        ->assertJsonPath('data.scope', 'self')
        ->assertJsonPath('data.employee.id', $employee->id)
        ->assertJsonPath('data.checked_in_today', true)
        ->assertJsonMissingPath('data.active_employees')
        ->assertJsonMissingPath('data.recent_hires')
        ->assertJsonStructure([
            'data' => [
                'scope',
                'employee',
                'unread_notifications',
                'today_attendance',
                'checked_in_today',
                'pending_leave_requests',
                'leave_balances',
                'my_attendance_last_7_days',
                'upcoming',
                'pending_actions',
                'recent_activity',
            ],
        ]);

    expect(count($this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->getJson('/api/dashboard/summary')
        ->json('data.my_attendance_last_7_days')))->toBe(7);
});
