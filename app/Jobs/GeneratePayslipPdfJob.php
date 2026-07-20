<?php

namespace App\Jobs;

use App\Models\Payslip;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class GeneratePayslipPdfJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $payslipId) {}

    public function handle(): void
    {
        $payslip = Payslip::query()->with('employee')->find($this->payslipId);

        if ($payslip === null) {
            return;
        }

        $employeeName = $payslip->employee?->full_name ?? ('Employee #'.$payslip->employee_id);
        $text = sprintf(
            'Payslip #%d | %s | %s to %s | Gross %s | Net %s',
            $payslip->id,
            $employeeName,
            $payslip->period_start?->toDateString(),
            $payslip->period_end?->toDateString(),
            (string) $payslip->gross,
            (string) $payslip->net,
        );

        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $body = "%PDF-1.1\n"
            ."1 0 obj<<>>endobj\n"
            ."trailer<< /Root 1 0 R >>\n"
            ."%%EOF\n"
            ."% {$escaped}\n";

        $path = 'payslips/'.$payslip->id.'.pdf';
        Storage::disk('local')->put($path, $body);

        $payslip->pdf_path = $path;
        $payslip->save();
    }
}
