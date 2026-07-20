<?php

use App\Exceptions\DomainException;
use App\Jobs\GenerateReportExportJob;
use App\Models\Company;
use App\Models\Employee;
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

test('dashboard summary returns expected keys', function () {
    Company::factory()->create();
    $user = reportUser(['can_view_employee_reports']);

    $this->actingAs($user)->withHeaders(spaJsonHeaders())
        ->getJson('/api/dashboard/summary')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'active_employees',
                'pending_leave_requests',
                'open_payroll_runs',
                'unread_notifications',
            ],
        ]);
});
