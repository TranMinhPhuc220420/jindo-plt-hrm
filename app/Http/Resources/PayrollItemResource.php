<?php

namespace App\Http\Resources;

use App\Models\PayrollItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PayrollItem
 */
class PayrollItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payroll_run_id' => $this->payroll_run_id,
            'employee_id' => $this->employee_id,
            'employee_code' => $this->whenLoaded('employee', fn () => $this->employee?->code),
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee?->full_name),
            'gross' => (string) $this->gross,
            'net' => (string) $this->net,
            'components' => $this->components ?? [],
        ];
    }
}
