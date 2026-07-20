<?php

namespace App\Http\Resources;

use App\Models\Payslip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payslip
 */
class PayslipResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'payroll_run_id' => $this->payroll_run_id,
            'payroll_item_id' => $this->payroll_item_id,
            'employee_id' => $this->employee_id,
            'employee_code' => $this->whenLoaded('employee', fn () => $this->employee?->code),
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee?->full_name),
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'gross' => (string) $this->gross,
            'net' => (string) $this->net,
            'components' => $this->components ?? [],
            'has_pdf' => $this->pdf_path !== null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
