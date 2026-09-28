<?php

use App\Jobs\GeneratePayslipPdfJob;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\Payslip;
use Illuminate\Support\Facades\Storage;

test('GeneratePayslipPdfJob writes a pdf and updates payslip pdf_path', function () {
    Storage::fake('local');

    $company = Company::factory()->create();
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'full_name' => 'Nguyen Van A',
    ]);
    $run = PayrollRun::factory()->create(['company_id' => $company->id]);
    $item = PayrollItem::factory()->create([
        'company_id' => $company->id,
        'payroll_run_id' => $run->id,
        'employee_id' => $employee->id,
    ]);
    $payslip = Payslip::factory()->create([
        'company_id' => $company->id,
        'payroll_run_id' => $run->id,
        'payroll_item_id' => $item->id,
        'employee_id' => $employee->id,
        'pdf_path' => null,
        'gross' => 10_000_000,
        'net' => 9_000_000,
    ]);

    (new GeneratePayslipPdfJob($payslip->id))->handle();

    $payslip->refresh();
    expect($payslip->pdf_path)->toBe('payslips/'.$payslip->id.'.pdf');
    Storage::disk('local')->assertExists($payslip->pdf_path);
    expect(Storage::disk('local')->get($payslip->pdf_path))->toContain('%PDF');
});

test('GeneratePayslipPdfJob no-ops when payslip is missing', function () {
    Storage::fake('local');

    (new GeneratePayslipPdfJob(999_999))->handle();

    expect(Storage::disk('local')->allFiles())->toBe([]);
});
