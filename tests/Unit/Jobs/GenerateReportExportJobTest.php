<?php

use App\Events\ReportExportReady;
use App\Jobs\GenerateReportExportJob;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ReportExport;
use App\Services\Report\ReportService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

test('GenerateReportExportJob writes csv marks ready and dispatches ReportExportReady', function () {
    Storage::fake('local');
    Event::fake([ReportExportReady::class]);

    $company = Company::factory()->create();
    [$user] = actingUserInCompany($company, ['can_view_employee_reports'], 'exp');
    Employee::factory()->create(['company_id' => $company->id, 'status' => 'active', 'code' => 'EXP-1']);

    $export = ReportExport::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'report' => 'employees',
        'filters' => [],
        'status' => 'pending',
        'path' => null,
    ]);

    session(['company_id' => $company->id]);

    (new GenerateReportExportJob($export->id))->handle(app(ReportService::class));

    $export->refresh();
    expect($export->status)->toBe('ready')
        ->and($export->path)->toBe('exports/report-'.$export->id.'.csv')
        ->and($export->error_message)->toBeNull();

    Storage::disk('local')->assertExists($export->path);
    expect(Storage::disk('local')->get($export->path))->toContain('EXP-1');

    Event::assertDispatched(ReportExportReady::class);
});

test('GenerateReportExportJob marks failed when report type is invalid', function () {
    Storage::fake('local');
    Event::fake([ReportExportReady::class]);

    $company = Company::factory()->create();
    [$user] = actingUserInCompany($company, ['can_view_employee_reports'], 'exp2');

    $export = ReportExport::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'report' => 'not-a-real-report',
        'status' => 'pending',
    ]);

    session(['company_id' => $company->id]);

    (new GenerateReportExportJob($export->id))->handle(app(ReportService::class));

    $export->refresh();
    expect($export->status)->toBe('failed')
        ->and($export->error_message)->not->toBeEmpty();

    Event::assertNotDispatched(ReportExportReady::class);
});
